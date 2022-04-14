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

namespace Ikarus\SPS\Access;


use Ikarus\SPS\Exception\GPIODriverException;

class MethodForwarderAccess implements AccessInstanceInterface
{
	/** @var object */
	private $object;

	/** @var string */
	private $propertyName;

	/**
	 * MethodForwarderAccess constructor.
	 * @param object $object
	 * @param string $propertyName
	 */
	public function __construct(object $object, string $propertyName)
	{
		$this->object = $object;
		$this->propertyName = $propertyName;

		if(!is_callable([$object, "get$propertyName"]) || !is_callable([$object, "set$propertyName"]))
			throw new GPIODriverException("Invalid object passed. Forwarder methods set* and get* of property $propertyName do not exist");
	}

	/**
	 * @return object
	 */
	public function getObject(): object
	{
		return $this->object;
	}

	/**
	 * @return string
	 */
	public function getPropertyName(): string
	{
		return $this->propertyName;
	}


	/**
	 * @inheritDoc
	 */
	public function getValue()
	{
		return call_user_func([$this->getObject(), "get".$this->getPropertyName()]);
	}

	/**
	 * @inheritDoc
	 */
	public function setValue($value)
	{
		call_user_func([$this->getObject(), "set".$this->getPropertyName()]);
		return $value;
	}
}