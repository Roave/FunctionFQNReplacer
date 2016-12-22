<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

declare(strict_types=1);

namespace RoaveBench\FunctionFQNReplacer;

/**
 * Benchmark that provides baseline results for simple absolute versus relative method reference calls
 *
 * Note that more functions can be added here: need to look up which ones are optimized
 * in https://github.com/php/php-src/blob/PHP-7.1/Zend/zend_compile.c
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class AbsoluteFunctionReferenceBench
{
    /**
     * @var callable
     */
    private $emptyFunction;

    public function __construct()
    {
        $this->emptyFunction = function () {
        };
    }

    public function benchCallUserFuncWithRelativeReference() : void
    {
        call_user_func($this->emptyFunction);
    }

    public function benchCallUserFuncWithAbsoluteReference() : void
    {
        \call_user_func($this->emptyFunction);
    }
}
