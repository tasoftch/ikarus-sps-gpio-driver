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


use Ikarus\Raspberry\Pinout\PinoutInterface;
use Ikarus\SPS\Access\AccessInstanceInterface;

abstract class AbstractPinoutDependingHandler implements HandlerInterface, HandlerPreparationInterface
{
	/** @var PinoutInterface */
	private $pinout;
	/** @var bool */
	private $bypass;
	/** @var bool */
	private $loadedImmediately;

	/** @var string[] */
	private $availablePinCodes = [];

	/**
	 * AbstractPinoutDependingHandler constructor.
	 * @param PinoutInterface $pinout
	 * @param bool $loadImmediately
	 * @param bool $bypass
	 */
	public function __construct(PinoutInterface $pinout, bool $loadImmediately = false, bool $bypass = false)
	{
		$this->pinout = $pinout;
		$this->bypass = $bypass;
		$this->loadedImmediately = $loadImmediately;

		if($loadImmediately)
			$this->availablePinCodes = $this->loadPinoutEnvironment($pinout, $bypass);
	}

	/**
	 * This method gets called to load the pinout and the available pin numbers.
	 * All supported pin numbers must be returned.
	 *
	 * @param PinoutInterface $pinout
	 * @param bool $asBypass
	 */
	abstract protected function loadPinoutEnvironment(PinoutInterface $pinout, bool $asBypass);

	/**
	 * @return string[]
	 */
	public function getAvailablePinCodes(): array
	{
		return $this->availablePinCodes;
	}

	/**
	 * Called to create the access instance
	 *
	 * @param string $code
	 * @param int $pinNumber
	 * @return AccessInstanceInterface|callable
	 */
	abstract protected function makeAccessInstance(string $code, int $pinNumber);

	/**
	 * Handler codes to match identification
	 *
	 * @return string[]
	 */
	abstract protected function getHandlerCodes(): array;

	/**
	 * @return PinoutInterface
	 */
	public function getPinout(): PinoutInterface
	{
		return $this->pinout;
	}

	/**
	 * @return bool
	 */
	public function isBypass(): bool
	{
		return $this->bypass;
	}

	public function prepare()
	{
		if(!$this->isLoadedImmediately())
			$this->loadPinoutEnvironment($this->pinout, $this->bypass);
	}

	public function getIdentificationPattern(): string
	{
		$tk = join('|', array_map(function($v) {
			return preg_quote( $v );
		}, $this->getHandlerCodes()));

		return "/^\s*($tk)\s*\.\s*(\d+)\s*$/";
	}

	public function getAccessInstance(array $matches)
	{
		$code = $matches[1];
		$pin = $matches[2]*1;

		if(in_array("$code.$pin", $this->availablePinCodes)) {
			return $this->makeAccessInstance($code, $pin);
		}
		return NULL;
	}

	/**
	 * @return bool
	 */
	public function isLoadedImmediately(): bool
	{
		return $this->loadedImmediately;
	}
}