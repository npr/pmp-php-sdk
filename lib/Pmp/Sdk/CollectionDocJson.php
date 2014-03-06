<?php
namespace Pmp\Sdk;

require_once('CollectionDocJsonLinks.php');
require_once('CollectionDocJsonItems.php');
require_once('Exception.php');
require_once(dirname(__FILE__) . '/../../restagent/restagent.lib.php');
require_once(dirname(__FILE__) . '/../../guzzle.phar');

use restagent\Request as Request;
use Guzzle\Http\Client as Client;
use Guzzle\Parser\UriTemplate\UriTemplate as UriTemplate;


class CollectionDocJson
{
    private $_uri;
    private $_auth;
    private $_readOnlyLinks;

    /**
     * @param string $uri
     *    URI for a Collection.doc+json document
     * @param AuthClient $auth
     *    authentication client
     * @throws Exception
     */
    public function __construct($uri, AuthClient $auth) {
        $this->_uri = trim($uri, '/'); // no trailing slash
        $this->_auth = $auth;

        // Retrieve the document from the given URL. Document is never empty. It will throw exception if it is empty.
        $document = $this->getDocument($uri);

        // Extract read-only links needed by the client
        $this->extractReadOnlyLinks($document);

        // Map the document properties to this object's properties
        $this->setDocument($document);
    }

    /**
     * Gets the set of links from the document that are associated with the given link relation
     * @param string $relType
     *     link relation of the set of links to get from the document
     * @return CollectionDocJsonLinks
     */
    public function links($relType) {
        $links = array();
        if (!empty($this->_readOnlyLinks->$relType)) {
            $links = $this->_readOnlyLinks->$relType;
        } else if (!empty($this->links->$relType)) {
            $links = $this->links->$relType;
        }
        return new CollectionDocJsonLinks($links, $this->_auth);
    }

    /**
     * Saves the current document
     * @return CollectionDocJson
     * @throws Exception
     */
    public function save() {

        // Determine where to save the document
        $saveUri = $this->getSaveUri();

        // Save the document
        $uri = $this->putDocument($saveUri);

        // Set new document URI
        if (!empty($uri)) {
            $this->_uri = $uri;
        }

        return $this;
    }

    /**
     * Deletes the current document
     * @return CollectionDocJson
     * @throws Exception
     */
    public function delete() {

        // Determine uri
        $uri = $this->getSaveUri();
        if (!$uri) {
            $exception = new Exception("Cannot delete a document with no URI set");
            throw $exception;
        }

        // Delete the document
        $this->deleteDocument($uri);

        return $this;
    }

    /**
     * Gets the set of items from the document
     * @return CollectionDocJsonItems
     */
    public function items() {
        $items = array();
        if (!empty($this->items)) {
            $items = $this->items;
        }
        return new CollectionDocJsonItems($items, $this);
    }

    /**
     * Gets a default "query" relation link that has the given URN
     * @param string $urn
     *    the URN associated with the desired "query" link
     * @return CollectionDocJsonLink
     */
    public function query($urn) {
        $urnQueryLink = null;
        $queryLinks = $this->links('query');

        // Lookup rels by given URN if query links found in document
        if (!empty($queryLinks)) {
            $urnQueryLinks = $queryLinks->rels(array($urn));

            // Use the first link found for the given URN if found
            if (!empty($urnQueryLinks[0])) {
                $urnQueryLink = $urnQueryLinks[0];
            }
        }
        return ($urnQueryLink) ? $urnQueryLink : new CollectionDocJsonLink(new \stdClass, $this->_auth);
    }

    /**
     * Gets a default "edit" relation link that has the given URN
     * @param string $urn
     *    the URN associated with the desired "edit" link
     * @return CollectionDocJsonLink
     */
    public function edit($urn) {
        $urnEditLink = null;
        $editLinks = $this->links('edit');

        // Lookup rels by given URN if edit links found in document
        if (!empty($editLinks)) {
            $urnEditLinks = $editLinks->rels(array($urn));

            // Use the first link found for the given URN if found
            if (!empty($urnEditLinks[0])) {
                $urnEditLink = $urnEditLinks[0];
            }
        }
        return ($urnEditLink) ? $urnEditLink : new CollectionDocJsonLink(new \stdClass, $this->_auth);
    }


    /**
     * Uploads the given media file and returns its new URL
     * @param $filepath
     * @return string
     */
    public function upload($filepath) {
        return $this->postFile($this->getFilesUri(), $filepath);
    }

    /**
     * Does a GET operation on the given URI and returns a JSON object
     * @param $uri
     *    the URI to use in the request
     * @return stdClass
     * @throws Exception
     */
    private function getDocument($uri) {
        $request = new Request();

        // GET request needs an authorization header with given access token
        $accessToken = $this->getAccessToken();
        $response = $request->header('Authorization', 'Bearer ' . $accessToken)
                            ->get($uri);

        // Retry authentication if request was unauthorized
        if ($response['code'] == 401) {
            $accessToken = $this->getAccessToken(true);
            $response = $request->header('Authorization', 'Bearer ' . $accessToken)
                                ->get($uri);
        }

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            $err = "Got unexpected non-HTTP-200 response and/or empty document while retrieving \"$uri\" with access Token: \"$accessToken\"";
            $exception = new Exception($err, $response['code']);
            $exception->setDetails($response);
            throw $exception;
            return null;
        }

        $document = json_decode($response['data']);
        $document->raw_response = $response;
        return $document;
    }

    /**
     * Does a DELETE operation on the given URI and returns true on success
     * @param $uri
     *    the URI to use in the request
     * @return true on success
     * @throws Exception
     */
    private function deleteDocument($uri) {
        $request = new Request();

        // DELETE request needs an authorization header with given access token
        $accessToken = $this->getAccessToken();
        $response = $request->header('Authorization', 'Bearer ' . $accessToken)
                            ->delete($uri);

        // Retry authentication if request was unauthorized
        if ($response['code'] == 401) {
            $accessToken = $this->getAccessToken(true);
            $response = $request->header('Authorization', 'Bearer ' . $accessToken)
                                ->header('Content-Type', 'application/vnd.pmp.collection.doc+json')
                                ->delete($uri);
        }

        // Response code must be 204 (no content)
        if ($response['code'] != 204) {
            $err = sprintf("Got HTTP response %s, expected 204, for DELETE '%s' with access Token: '%s'",
                   $response['code'], $uri, $accessToken);
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return null;
        }

        return true;
    }

    /**
     * Does a PUT operation on the given URI using the internal JSON objects
     * @param $uri
     *    the URI to use in the request
     * @return string
     * @throws Exception
     */
    private function putDocument($uri) {

        // Construct the document from the allowable properties in this object
        $document = json_encode($this->buildDocument());

        $request = new Request();

        // PUT request needs an authorization header with given access token and
        // the JSON-encoded body based on the document content
        $accessToken = $this->getAccessToken();
        $response = $request->header('Content-Type', 'application/vnd.collection.doc+json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->body($document)
                            ->put($uri);

        // Retry authentication if request was unauthorized
        if ($response['code'] == 401) {
            $accessToken = $this->getAccessToken(true);
            $response = $request->header('Content-Type', 'application/vnd.collection.doc+json')
                ->header('Authorization', 'Bearer ' . $accessToken)
                ->body($document)
                ->put($uri);
        }

        // Response code must be 200 or 202 in order to be successful
        if ($response['code'] != 200 && $response['code'] != 202) {
            $err = "Got unexpected non-HTTP-200 and non-HTTP-202 response while sending \"$uri\" with access Token: \"$accessToken\"";
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return '';
        }

        // Return saved document URI if available
        if (!empty($response['data'])) {
            $data = json_decode($response['data']);
            return $data->url;
        } else {
            return '';
        }
    }

    /**
     * Does a POST operation on the given URI using the given file path. Returns the URL of the upload file.
     * @param $uri
     *    the URI to use in the request
     * @param $file
     *    the file path to use for uploading
     * @return string
     * @throws Exception
     */
    private function postFile($uri, $file) {

        // Using Guzzle instead of Restagent because of file stream upload support
        $request = new Client();

        // POST request needs an authorization header with given access token and
        // the multipart form-data body
        $accessToken = $this->getAccessToken();
        $response = $request->post($uri, array(
            'Authorization' => 'Bearer ' . $accessToken
        ))->addPostFile('submission', $file)->send();

        // Retry authentication if request was unauthorized
        if ($response->getStatusCode() == 401) {
            $accessToken = $this->getAccessToken(true);
            $response = $request->post($uri, array(
                'Authorization' => 'Bearer ' . $accessToken
            ))->addPostFile('submission', $file)->send();
        }

        // Response code must be 202 in order to be successful
        if ($response->getStatusCode() != 202) {
            $err = "Got unexpected non-HTTP-202 response while sending \"$uri\" with access Token: \"$accessToken\"";
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return '';
        }

        $body = $response->getBody();
        if (!empty($body)) {
            $body = json_decode($body);
            if (!empty($body->url)) {
                return $body->url;
            }
        }
        return '';
    }

    /**
     * Gets an access token from the authentication client
     * @param bool $refresh
     *   whether to refresh the token
     * @return string
     */
    public function getAccessToken($refresh=false) {
        return $this->_auth->getToken($refresh)->access_token;
    }

    /**
     * Creates a new guid, either from the API, or by generating a compatible UUID
     * @param bool $useApi
     *     whether to go get the guid from the API first
     * @return string
     */
    public function createGuid($useApi=false) {
        if ($useApi) {
            try {
                $guid = $this->getGuid($this->getGuidsUri());
                if ($guid) {
                    return $guid;
                }
            } catch (\Exception $e) {
                // do nothing - just generate a UUID instead
            }
        }
        return $this->generateUuid();
    }

    /**
     * Generates a guid using UUID v4 based on RFC 4122
     *
     * @see http://tools.ietf.org/html/rfc4122#section-4.4
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     *
     * @return string
     */
    private function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time-low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time-mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time-hi-and-version", four most significant bits are 0100 (so first hex digit is 4, for UUID version 4)
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clock-seq-hi-and-reserved", 8 bits for "clock_seq_low", two most significant bits are 10 (so first hex digit is 8, 9, A, or B)
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Does a POST operation on the given URI to get a new random guid
     * @param $uri
     *    the URI to use in the request
     * @return string
     * @throws Exception
     */
    private function getGuid($uri) {
        $request = new Request();

        // POST request needs an authorization header with given access token
        $accessToken = $this->getAccessToken();
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->body('{"count":1}')
                            ->post($uri);

        // Retry authentication if request was unauthorized
        if ($response['code'] == 401) {
            $accessToken = $this->getAccessToken(true);
            $response = $request->header('Content-Type', 'application/json')
                                ->header('Authorization', 'Bearer ' . $accessToken)
                                ->body('{"count":1}')
                                ->post($uri);
        }

        // Response code must be 200 in order to be successful
        if ($response['code'] != 200) {
            $err = "Got unexpected non-HTTP-200 response while POSTing to \"$uri\" with access Token: \"$accessToken\"";
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return '';
        }

        $data = json_decode($response['data']);
        return $data->guids[0];
    }

    /**
     * Extracts important read-only links from the document
     * @param \stdClass $document
     * @return CollectionDocJson
     */
    private function extractReadOnlyLinks(\stdClass $document) {
        if (is_object($document)) {
            if (!isset($this->_readOnlyLinks)) {
                $this->_readOnlyLinks = new \stdClass;
            }
            if (!empty($document->links->query)) {
                $this->_readOnlyLinks->query = $document->links->query;
            }
            if (!empty($document->links->edit)) {
                $this->_readOnlyLinks->edit = $document->links->edit;
            }
        }
        return $this;
    }

    /**
     * Clears the current document from the object
     * @return CollectionDocJson
     */
    public function clearDocument() {
        unset($this->version);
        unset($this->attributes);
        unset($this->links);
        unset($this->items);
        unset($this->error);

        return $this;
    }

    /**
     * Builds the current document from the writeable document properties of the object
     * @return \stdClass
     */
    public function buildDocument() {
        $document = new \stdClass();
        $document->version = (!empty($this->version)) ? $this->version : null;
        $document->attributes = (!empty($this->attributes)) ? $this->attributes : null;
        $document->links = (!empty($this->links)) ? $this->links : null;

        return $document;
    }

    /**
     * Sets the given document on the object
     * @param \stdClass $document
     * @return CollectionDocJson
     */
    public function setDocument(\stdClass $document) {
        $this->clearDocument();

        if (is_object($document)) {
            $properties = get_object_vars($document);
        } else {
            $properties = array();
        }

        foreach($properties as $name => $value) {
            $this->$name = $value;
        }

        return $this;
    }

    /**
     * Build the URI for saving the document
     * @return string
     */
    public function getSaveUri() {
        // Make sure there is a guid to save to
        if (empty($this->attributes->guid)) {
            $this->attributes->guid = $this->createGuid();
        }

        // Make sure there is an edit-form link to save to
        $editLink = $this->edit("urn:collectiondoc:form:documentsave");
        if (!empty($editLink->{'href-template'})) {
            if (!empty($this->attributes->guid)) {
                $parser = new UriTemplate();
                $url = $parser->expand($editLink->{'href-template'}, array('guid' => $this->attributes->guid));
                return $url;
            }
        }

        return '';
    }

    /**
     * Get the URI for retrieving guids
     * @return string
     */
    public function getGuidsUri() {
        $guidsLink = $this->query("urn:collectiondoc:query:guids");
        if (!empty($guidsLink->href)) {
            return $guidsLink->href;
        } else {
            return '';
        }
    }

    /**
     * Get the URI for uploading files
     * @return string
     */
    public function getFilesUri() {
        $filesLink = $this->edit("urn:collectiondoc:form:mediaupload");
        if (!empty($filesLink->href)) {
            return $filesLink->href;
        } else {
            return '';
        }
    }

    /**
     * Get the URI of the current document
     * @return string
     */
    public function getUri() {
        return $this->_uri;
    }

    /** 
     * Convenience static method for searching the docs URN.
     * @param string $host
     * @param AuthClient $auth
     * @param array $options
     * @return CollectionDocJson $results
     * @throws Exception
     */
    public static function search($host, $auth, array $options) {
        $searcher = new CollectionDocJson($host, $auth);
        $results  = null;
        try {
            $results = $searcher->query('urn:collectiondoc:query:docs')->submit($options);
        } catch (Exception $ex) {

            // 404 throws an exception, but no results on a search is normal.
            if ($ex->getCode() != 404) {
                // re-throw if response was not 200 or 404
                throw $ex;
            }   
        }   
        return $results;
    }   

}



function pmp_backtrace() {
    $trace = debug_backtrace();
    foreach ($trace as &$t) {
        unset($t['args']);
        unset($t['object']);
    }
    return $trace;
}
