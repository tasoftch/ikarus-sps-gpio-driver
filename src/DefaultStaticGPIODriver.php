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


use Ikarus\SPS\Exception\OccupiedIdentificationPatternException;
use Ikarus\SPS\Handler\HandlerInterface;
use Ikarus\SPS\Handler\HandlerObserverInterface;
use Ikarus\SPS\Handler\HandlerPreparationInterface;

class DefaultStaticGPIODriver implements GPIODriverInterface
{
	/** @var HandlerInterface[] */
	private $handlers = [];
	/** @var HandlerInterface[] */
	private $newHandlers = [];

	private $handlerCache = [];

	/** @var static */
	protected static $globalDriver;


	/**
	 * @inheritDoc
	 */
	public static function getDriver()
	{
		if(!self::$globalDriver)
			self::$globalDriver = new static();
		return self::$globalDriver;
	}

	protected function getHandlers(): array {
		if($this->newHandlers) {
			ksort($this->newHandlers);
			foreach($this->newHandlers as $handlers) {
				foreach($handlers as $handler) {
					if(isset($this->handlers[ $ptrn = $handler->getIdentificationPattern() ]))
						throw (new OccupiedIdentificationPatternException("Pattern $ptrn is already in use"))->setHandler($handler);
					if($handler instanceof HandlerPreparationInterface)
						$handler->prepare();
					$this->handlers[$ptrn] = $handler;
				}
			}
			$this->newHandlers = [];
		}
		return $this->handlers;
	}

	/**
	 * @inheritDoc
	 */
	public function registerHandler(HandlerInterface $aHandler)
	{
		$this->newHandlers[ $aHandler->getPriority() ][] = $aHandler;
	}

	/**
	 * @inheritDoc
	 */
	public function requireAccess(string $identification): ?callable
	{
		if(isset($this->handlerCache[$identification]))
			return $this->handlerCache[$identification];

		foreach($this->getHandlers() as $pattern => $handler) {
			if(preg_match($pattern, $identification, $ms)) {
				$access = $handler->getAccessInstance( $ms );
				if($access) {
					return $this->handlerCache[$identification] = function($v = NULL) use ($identification, $access, $handler) {
						if(NULL === $v)
							return is_callable($access) ? $access() : $access->getValue();
						if($handler instanceof HandlerObserverInterface)
							$handler->accessWillChange($access, $identification, $v);
						if(is_callable($access))
							$access($v);
						else
							$access->setValue($v);
						return $v;
					};
				} else
					trigger_error("No access handler found for identification $identification", E_USER_WARNING);
			}
		}
		return NULL;
	}
}