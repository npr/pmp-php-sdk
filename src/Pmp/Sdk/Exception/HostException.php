<?php
namespace Pmp\Sdk\Exception;

/**
 * API unknown-host errors
 *
 * Also catches some very specific "host found but this is not the home doc"
 * errors, when instantiating the SDK.
 */
class HostException extends RemoteException {

}
