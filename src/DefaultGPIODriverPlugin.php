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

namespace Ikarus\SPS;


use Ikarus\SPS\Handler\PostHandlerInterface;
use Ikarus\SPS\Handler\PreHandlerInterface;
use Ikarus\SPS\Plugin\CycleAwarePluginInterface;
use Ikarus\SPS\Plugin\PluginInterface;
use Ikarus\SPS\Register\MemoryRegisterInterface;

class DefaultGPIODriverPlugin extends DefaultStaticGPIODriver implements PluginInterface, CycleAwarePluginInterface
{
	private $preHandlers = [];
	private $postHandlers = [];

	public function getIdentifier(): string
	{
		return "GPIO_DRIVER";
	}

	public function getDomain(): string
	{
		return "DEFAULT";
	}

	public function initialize(MemoryRegisterInterface $memoryRegister)
	{
		foreach($this->getHandlers() as $handler) {
			if($handler instanceof PreHandlerInterface)
				$this->preHandlers[] = $handler;
			if($handler instanceof PostHandlerInterface)
				$this->postHandlers[] = $handler;
		}
	}

	public function beginCycle(MemoryRegisterInterface $memoryRegister)
	{
		if($this->preHandlers) {
			array_walk($this->preHandlers, function(PreHandlerInterface $handler) {
				$handler->readValues();
			});
		}
	}

	public function endCycle(MemoryRegisterInterface $memoryRegister)
	{
		if($this->postHandlers) {
			array_walk($this->postHandlers, function(PostHandlerInterface $handler) {
				$handler->writeValues();
			});
		}
	}


	public function update(MemoryRegisterInterface $memoryRegister)
	{
	}
}