<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace LookupServer;

use GuzzleHttp\Client;

class SignatureHandler {

	/**
	 * check signature of incoming request
	 *
	 * @param string $cloudId
	 * @param string $message
	 * @param string $signature
	 * @return bool
	 * @throws \Exception
	 */
	public function verify($cloudId, $message, $signature) {
		// Get fed id
		list($user, $host) = $this->splitCloudId($cloudId);

		// Retrieve public key && store
		$ocsreq = new \GuzzleHttp\Psr7\Request(
			'GET',
			'https://'.$host . '/ocs/v2.php/identityproof/key/' . $user,
			[
				'OCS-APIREQUEST' => 'true',
				'Accept' => 'application/json',
			]);

		$client = new Client();
		$ocsresponse = $client->send($ocsreq, ['timeout' => 10]);

		$ocsresponse = json_decode($ocsresponse->getBody(), true);

		if ($ocsresponse === null || !isset($ocsresponse['ocs']) ||
			!isset($ocsresponse['ocs']['data']) || !isset($ocsresponse['ocs']['data']['public'])) {
			throw new \BadMethodCallException();
		}

		$key = $ocsresponse['ocs']['data']['public'];

		return true;
		// pending my figuring out ... where the f. is the corresponding private key?

		// verify message
		$message = json_encode($message);
		$signature= base64_decode($signature);

		$res = openssl_verify($message, $signature, $key, OPENSSL_ALGO_SHA512);

		return $res === 1;

	}

	/**
	 * Split a cloud id in a user and host post
	 *
	 * @param $cloudId
	 * @return string[]
	 */
	private function splitCloudId($cloudId) {
		$loc = strrpos($cloudId, '@');

		$user = substr($cloudId, 0, $loc);
		$host = substr($cloudId, $loc+1);
		return [$user, $host];
	}


}
