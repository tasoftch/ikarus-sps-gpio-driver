<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Ikarus\SPS\Handler;

use Closure;
use Ikarus\SPS\I2C\Extender_16GPIO_MCP23017;

class MCP23017Handler implements HandlerInterface
{
	/** @var Extender_16GPIO_MCP23017 */
	protected $mcp;
	/** @var string */
	private $accessCode;
	private $pinout;
	private $setupChip;

	private $secureWritingEnabled = false;

	/**
	 * MCP23017Handler constructor.
	 * @param Extender_16GPIO_MCP23017 $mcp
	 * @param string $accessCode
	 * @param array|null $pinout
	 * @param bool $setupChip
	 */
	public function __construct(Extender_16GPIO_MCP23017 $mcp, string $accessCode = 'M32', array $pinout = NULL, bool $setupChip = true)
	{
		$this->mcp = $mcp;
		$this->accessCode = $accessCode;
		$this->pinout = $pinout;
		$this->setupChip = $setupChip;
	}


	/**
	 * @inheritDoc
	 */
	public function getIdentificationPattern(): string
	{
		$c = preg_quote($this->getAccessCode());
		return "/^\s*$c\s*\.\s*(\d+)\s*$/";
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority(): int
	{
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getAccessInstance(array $matches)
	{
		if(is_array($this->pinout)) {
			$this->mcp->setupPins($this->pinout, $this->setupChip);
			$this->pinout = NULL;
		}
		$pin = $matches[1] * 1;
		return $this->makeAccessCallback($pin);
	}

	/**
	 * @param $pin
	 * @return Closure
	 */
	protected function makeAccessCallback($pin): Closure
	{
		return function($v = NULL) use ($pin) {
			if($v === NULL)
				return $this->mcp->digitalReadPin($pin);
			if($this->isSecureWritingEnabled())
				$this->mcp->digitalWritePinSecure($pin, $v ? 1 : 0);
			else
				$this->mcp->digitalWritePin($pin, $v ? 1 : 0);
			return $v;
		};
	}

	/**
	 * @return string
	 */
	public function getAccessCode(): string
	{
		return $this->accessCode;
	}

	/**
	 * @return bool
	 */
	public function isSecureWritingEnabled(): bool
	{
		return $this->secureWritingEnabled;
	}

	/**
	 * @param bool $secureWritingEnabled
	 * @return static
	 */
	public function setSecureWritingEnabled(bool $secureWritingEnabled)
	{
		$this->secureWritingEnabled = $secureWritingEnabled;
		return $this;
	}
}