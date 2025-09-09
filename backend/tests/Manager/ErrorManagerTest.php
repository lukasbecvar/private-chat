<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManagerTest
 *
 * Test cases for error manager
 *
 * @package App\Tests\Manager
 */
class ErrorManagerTest extends TestCase
{
    private ErrorManager $errorManager;
    private AppUtil & MockObject $appUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);

        // create error manager instance
        $this->errorManager = new ErrorManager($this->appUtilMock);
    }

    /**
     * Test handle error exception
     *
     * @return void
     */
    public function testHandleError(): void
    {
        // expect http exception to be thrown
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Page not found');
        $this->expectExceptionCode(Response::HTTP_NOT_FOUND);

        // call tested method
        $this->errorManager->handleError(
            message: 'Page not found',
            code: Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Test handle error exception with exception message
     *
     * @return void
     */
    public function testHandleErrorWithExceptionMessage(): void
    {
        // simulate dev mode enabled
        $this->appUtilMock->method('isDevMode')->willReturn(true);

        // expect http exception to be thrown
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Page not found: exception message');
        $this->expectExceptionCode(Response::HTTP_NOT_FOUND);

        // call tested method
        $this->errorManager->handleError(
            message: 'Page not found',
            code: Response::HTTP_NOT_FOUND,
            exceptionMessage: 'exception message'
        );
    }

    /**
     * Test block exception message in production mode
     *
     * @return void
     */
    public function testHandleErrorInProductionMode(): void
    {
        // simulate dev mode disabled
        $this->appUtilMock->method('isDevMode')->willReturn(false);

        // expect http exception to be thrown
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Page not found');
        $this->expectExceptionCode(Response::HTTP_NOT_FOUND);

        // call tested method
        $this->errorManager->handleError(
            message: 'Page not found',
            code: Response::HTTP_NOT_FOUND,
            exceptionMessage: 'exception message'
        );
    }
}
