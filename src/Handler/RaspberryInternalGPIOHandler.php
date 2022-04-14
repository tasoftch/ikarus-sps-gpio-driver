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


use Ikarus\Raspberry\Pin\PinInterface;
use Ikarus\Raspberry\Pinout\PinoutInterface;
use Ikarus\Raspberry\RaspberryPiDevice;

class RaspberryInternalGPIOHandler extends AbstractPinoutDependingHandler
{
	const HARDWARE_BOARD_ASSIGNMENT = 1<<0;
	const BCM_BOARD_ASSIGNMENT = 1<<1;
	const WPI_BOARD_ASSIGNMENT = 1<<2;

	private $boardAssignments = 0x7;

	private $registeredPins = [];

	/**
	 * @return int
	 */
	public function getBoardAssignments(): int
	{
		return $this->boardAssignments;
	}

	/**
	 * @param int $boardAssignments
	 * @return RaspberryInternalGPIOHandler
	 */
	public function setBoardAssignments(int $boardAssignments): RaspberryInternalGPIOHandler
	{
		$this->boardAssignments = $boardAssignments;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	protected function loadPinoutEnvironment(PinoutInterface $pinout, bool $asBypass): array
	{
		$dev = RaspberryPiDevice::getDevice();

		$assign = function($bcm, PinInterface $pin) use ($dev) {
			if($this->getBoardAssignments() & self::BCM_BOARD_ASSIGNMENT)
				$this->registeredPins["B.$bcm"] = $pin;
			if($this->getBoardAssignments() & self::HARDWARE_BOARD_ASSIGNMENT)
				$this->registeredPins["H.".$dev->convertPinNumber( $bcm, $dev::GPIO_NS_BCM, $dev::GPIO_NS_BOARD )] = $pin;
			if($this->getBoardAssignments() & self::WPI_BOARD_ASSIGNMENT)
				$this->registeredPins["W.".$dev->convertPinNumber( $bcm, $dev::GPIO_NS_BCM, $dev::GPIO_NS_WIRED )] = $pin;
		};

		if($asBypass) {
			foreach($pinout->yieldInputPin($r,$a) as $bcm) {
				$assign($bcm, $dev->getPin($bcm));
			}
		} else {
			foreach($dev->requirePinout($pinout) as $bcm => $pin) {
				$assign($bcm, $pin);
			}
		}

		return array_keys($this->registeredPins);
	}

	/**
	 * @inheritDoc
	 */
	protected function makeAccessInstance(string $code, int $pinNumber)
	{
		return $this->registeredPins["$code.$pinNumber"];
	}

	/**
	 * @inheritDoc
	 */
	protected function getHandlerCodes(): array
	{
		$c = [];
		if($this->getBoardAssignments() & self::HARDWARE_BOARD_ASSIGNMENT) $c[] = 'H';
		if($this->getBoardAssignments() & self::BCM_BOARD_ASSIGNMENT) $c[] = 'B';
		if($this->getBoardAssignments() & self::WPI_BOARD_ASSIGNMENT) $c[] = 'W';
		return $c;
	}


	/**
	 * @inheritDoc
	 */
	public function getPriority(): int
	{
		return 10;
	}
}