<?php
/**
 * @file  pjlink.class.php
 * @brief Pure PHP PJLink Class1 library for operating and controlling data projectors
 *
 * @mainpage
 *
 * PJLink PHP class - PJLINK Class1 library
 *
 * http://developer.sysco.ch/
 *
 * The PJLink PHP class is the lightest pure PHP package available for
 * operating and controlling data projectors using the PJLink Class1 standard.
 *
 * PJLink Class1 specifications are available here:
 *   http://pjlink.jbmia.or.jp/english/data/5-1_PJLink_eng_20131210.pdf
 *
 * The Readme file contains additional information.
 *
 * PHP 5.3.0 or higher is supported.
 *
 * @author    Andre Liechti, SysCo systemes de communication sa, <developer@sysco.ch>
 * @version   1.0.0.1
 * @date      2017-04-24
 * @since     2017-04-23
 * @copyright (c) 2017 SysCo systemes de communication sa
 * @copyright GNU Lesser General Public License
 *
 *//*
 *
 * LICENCE
 *
 *   Copyright (c) 2017 SysCo systemes de communication sa
 *   SysCo (tm) is a trademark of SysCo systemes de communication sa
 *   (http://www.sysco.ch/)
 *   All rights reserved.
 * 
 *   PJLink PHP class is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the License,
 *   or (at your option) any later version.
 * 
 *   PJLink PHP class is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 * 
 *   You should have received a copy of the GNU Lesser General Public
 *   License along with PJLink PHP class.
 *   If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Usage
 *
 *   Every command send to the projector return false is something is wrong.
 *     The error message can be detailed by calling the getError() method.
 *
 *   Public methods available:
 *    setDevice($host, [[[$password], $timeout], $port])
 *    powerOn([[[[$host], $password], $timeout], $port])
 *    powerOff([[[[$host], $password], $timeout], $port])
 *    getPowerState([[[[$host], $password], $timeout], $port])
 *    setInput($input, [[[[$host], $password], $timeout], $port])
 *    getInput([[[[$host], $password], $timeout], $port])
 *    getErrorState([[[[$host], $password], $timeout], $port])
 *    getLampState([[[[$host], $password], $timeout], $port])
 *    getName([[[[$host], $password], $timeout], $port])
 *    getManufactureName([[[[$host], $password], $timeout], $port])
 *    getProductName([[[[$host], $password], $timeout], $port])
 *    getOtherInfo([[[[$host], $password], $timeout], $port])
 *    getClass([[[[$host], $password], $timeout], $port])
 *    getError()
 *    getErrorNumber()
 *    getResponseRaw()
 *    getResponseText()
 *
 *
 * Examples
 *
 *   // Example 1
 *   require_once('pjlink.class.php');
 *   $pjlink = new PJLink();
 *   if (false === $pjlink->powerOn("192.168.0.1")) {
 *     echo $pjlink->getError();
 *	 } elseif (false === $pjlink->setInput(11, "192.168.0.1")) {
 *     echo $pjlink->getError();
 *	 }
 *
 *
 *   // Example 2
 *   require_once('pjlink.class.php');
 *   $pjlink = new PJLink();
 *   $pjlink->setDevice("192.168.0.1", "my_pjlink_pass", 10, 4352);
 *   if (false === $pjlink->powerOn()) {
 *     echo $pjlink->getError();
 *	 } elseif (false === $pjlink->setInput(11)) {
 *     echo $pjlink->getError();
 *	 }
 *   echo "Device: ".$pjlink->getManufactureName().", ".$pjlink->getProductName()."<br />\n";
 *
 *
 * No external file is needed (no PEAR, no PECL, no cURL).
 *
 *
 * Special issues
 *
 *   If you need specific developements concerning strong authentication,
 *   do not hesistate to contact us per email at developer@sysco.ch.
 *
 *
 * Users feedbacks and comments
 *
 * 2017-04-24 SysCo/al (CH)
 *   First public version
 *
 *
 * Change Log
 *
 *   2017-04-24 1.0.0.1 SysCo/al First public version
 *   2017-04-23 1.0.0.0 SysCo/al Initial implementation
 *********************************************************************/

// PJLINK constants
define ("PJLINK_OK",              0);
define ("PJLINK_BAD_DEVICE",      20);
define ("PJLINK_NO_CONNECTION",   21);
define ("PJLINK_AUTH_ERROR",      22);
define ("PJLINK_SEND_ERROR",      23);

define ("PJLINK_CLASS",           "1");
define ("PJLINK_DEFAULT_PORT",    4352);
define ("PJLINK_DEFAULT_TIMEOUT", 5);
define ("PJLINK_PREFIX",          "%");

class PJLink
{
	var $host          = "";
	var $password      = "";
	var $timeout       = PJLINK_DEFAULT_TIMEOUT;
	var $port          = PJLINK_DEFAULT_PORT;
	var $socket        = NULL;
	var $error         = "";
	var $error_number  = "";
	var $prefix_hash   = "";
	var $response_raw  = "";
	var $response_text = "";

	public function getError()
	{
		return $this->error;
	}

	public function getErrorNumber()
	{
		return $this->error_number;
	}

	public function getResponseRaw()
	{
		return $this->response_raw;
	}

	public function getResponseText()
	{
		return $this->response_text;
	}

	public function setDevice(
		$host,
		$password = "",
		$timeout  = PJLINK_DEFAULT_TIMEOUT, 
		$port     = PJLINK_DEFAULT_PORT
	) {

		$this->error = "";
		$this->error_number = PJLINK_OK;

		$this->host     = $host;
		$this->password = $password;
		$this->timeout  = $timeout;
		$this->port     = $port;

		if ($host == '') {
			$this->error = "Host not defined";
			$this->error_number = PJLINK_BAD_DEVICE;
			return false;
		}

		return true;
	}

	private function open(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {

		$this->error = "";
		$this->error_number = PJLINK_OK;

		if ($host !== "") {
			$this->host = $host;
		}

		if ($password !== "") {
			$this->password = $password;
		}

		if ($timeout !== 0) {
			$this->timeout = intval($timeout);
		}

		if (intval($port) > 0) {
			$this->port = intval($port);
		}
	
		if ($this->host == '') {
			$this->error = "Host not defined";
			$this->error_number = PJLINK_BAD_DEVICE;
			return false;
		}

		$errno = 0;
		$errstr = "";

		$this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

		if (!$this->socket) {
			$this->error = "Connection failed: " . socket_strerror($errno);
			$this->error_number = PJLINK_NO_CONNECTION;
			return false;
		}

		stream_set_timeout($this->socket, $this->timeout, 0);

		$response = $this->getResponse();

		if (FALSE !== strpos($response, "PJLINK 0")) {
			$this->prefix_hash = "";
			return true;
		} elseif (FALSE !== strpos($response, "PJLINK 1")) {
			$auth_random = trim(substr($response, strpos($response, "PJLINK 1") + 9));
			$this->prefix_hash = md5($auth_random . $password);
			return true;
		} else {
			$this->error = "Bad answer, connection failed";
			$this->error_number = PJLINK_NO_CONNECTION;
			return false;
		}
	}


	private function sendCommand(
		$command
	) {

		$this->error = "";
		$this->error_number = PJLINK_OK;
		$this->response_raw = "";
		$this->response_text = "";

		if (!$this->socket) {
			$this->error = "Device not connected";
			$this->error_number = PJLINK_NO_CONNECTION;
			return false;
		}

		if (FALSE === fwrite($this->socket, $this->prefix_hash . $command . chr(13))) {
			$this->error = "Error sending command ".$command;
			$this->error_number = PJLINK_SEND_ERROR;
			return false;
		}

		$response = $this->getResponse();

		if (FALSE !== strpos($response, "PJLINK ERRA")) {
			$this->error = "Authentication failed";
			$this->error_number = PJLINK_AUTH_ERROR;
			return false;
		} elseif (FALSE !== strpos($response, PJLINK_PREFIX)) {
			$result = trim(substr($response, strpos($response, "=") + 1));
			if (0 === strpos($result, "ERR")) {
				$this->response_text = $result;
				return false;
			} else {
				return $result;
			}
		} else {
			$this->error = "Error sending command ".$command;
			$this->error_number = PJLINK_SEND_ERROR;
			return false;
		}
	}


	private function getResponse()
	{

		$this->error = "";
		$this->error_number = PJLINK_OK;
		$this->response_raw = "";
		$this->response_text = "";

		$response = '';
		if (!$this->socket) {
			$this->error = "Device not connected";
			$this->error_number = PJLINK_NO_CONNECTION;
			return false;
		}

		while (true) {
			$char = fgetc($this->socket);
			if (($char !== false) && ($char !== chr(13))) {
				$response.= $char;
			} else {
				$this->response_raw = $response;
				$this->response_text = $response;
				return $response;
			}
		}
	}


	private function close()
	{

		$this->error = "";
		$this->error_number = PJLINK_OK;

		if (!$this->socket) {
			$this->error = "Device not connected";
			$this->error_number = PJLINK_NO_CONNECTION;
			return false;
		} else {
			fclose($this->socket);
			return true;
		}
	}

	public function powerOn(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "POWR 1";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				switch ($result) {
				  default:
						$this->response_text = $result;
				}
			} else {
				switch ($this->response_text) {
					case 'ERR2':
						$this->response_text = "out-of-parameter";
					  break;
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function powerOff(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "POWR 0";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				switch ($result) {
				  default:
						$this->response_text = $result;
				}
			} else {
				switch ($this->response_text) {
					case 'ERR2':
						$this->response_text = "out-of-parameter";
					  break;
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getPowerState(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "POWR ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				switch ($result) {
					case '0':
						$this->response_text = "off";
					  break;
					case '1':
						$this->response_text = "on";
					  break;
					case '2':
						$this->response_text = "cooling";
					  break;
					case '3':
						$this->response_text = "warm-up";
					  break;
				  default:
						$this->response_text = $result;
				}
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function setInput(
		$input,
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "INPT " . $input;
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				switch ($result) {
				  default:
						$this->response_text = $result;
				}
			} else {
				switch ($this->response_text) {
					case 'ERR2':
						$this->response_text = "nonexistent";
					  break;
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getInput(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "INPT ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				switch ($result) {
				  default:
						$this->response_text = $result;
				}
			} else {
				switch ($this->response_text) {
					case 'ERR2':
						$this->response_text = "nonexistent";
					  break;
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getErrorState(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "ERST ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$this->response_text = "";
				if (substr($result, 0, 1) != "0") {
					$this->response_text.= ($this->response_text != "" ? ", " : "") . "FAN: " . ((substr($result, 0, 1) == "1") ? "WARNING" : "ERROR");
				}
				if (substr($result, 1, 1) != "0") {
					$this->response_text.= ($this->response_text != "" ? ", " : "") . "LAMP: " . ((substr($result, 1, 1) == "1") ? "WARNING" : "ERROR");
				}
				if (substr($result, 2, 1) != "0") {
					$this->response_text.= ($this->response_text != "" ? ", " : "") . "TEMPERATURE: " . ((substr($result, 2, 1) == "1") ? "WARNING" : "ERROR");
				}
				if (substr($result, 3, 1) != "0") {
					$this->response_text.= ($this->response_text != "" ? ", " : "") . "COVER OPEN: " . ((substr($result, 3, 1) == "1") ? "WARNING" : "ERROR");
				}
				if (substr($result, 4, 1) != "0") {
					$this->response_text.= ($this->response_text != "" ? ", " : "") . "FILTER: " . ((substr($result, 4, 1) == "1") ? "WARNING" : "ERROR");
				}
				if (substr($result, 5, 1) != "0") {
					$this->response_text.= ($this->response_text != "" ? ", " : "") . "OTHER: " . ((substr($result, 5, 1) == "1") ? "WARNING" : "ERROR");
				}
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getLampState(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "LAMP ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$lamps = explode(" ", $result);
				// TODO more details
				$this->response_text = $result;
			} else {
				switch ($this->response_text) {
					case 'ERR1':
						$this->response_text = "no-lamp";
					  break;
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getName(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "NAME ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$this->response_text = $result;
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getManufactureName(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "INF1 ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$this->response_text = $result;
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getProductName(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "INF2 ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$this->response_text = $result;
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getOtherInfo(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "INFO ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$this->response_text = $result;
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

	public function getClass(
		$host     = "",
		$password = "",
		$timeout  = 0,
		$port     = 0
	) {
		$command = PJLINK_PREFIX . PJLINK_CLASS . "CLSS ?";
		if ($this->open($host, $password, $timeout, $port)) {
			$result = $this->sendCommand($command);
			if (false !== $result) {
				$this->response_text = $result;
			} else {
				switch ($this->response_text) {
					case 'ERR3':
						$this->response_text = "unavailable";
					  break;
					case 'ERR4':
						$this->response_text = "failure";
					  break;
				}
				$this->error = $this->response_text;
			}
			return $result;
		} else {
			return false;
		}
	}

}