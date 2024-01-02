<?php

namespace MailQueue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @property \Cake\Mailer\Mailer email
 */
class QueueTransportTest extends TestCase
{
    private $mailer;
    private $config;
    private $unlockInTeardown;
    private $mTransport;

    public function setUp(): void
    {
        $this->mailer = new Mailer('default');
        $this->config = $this->mailer->getTransport()->getConfig(null);
        $this->mTransport = $this->getMockBuilder(\Cake\Mailer\AbstractTransport::class)
            ->onlyMethods(['send'])
            ->getMock();
    }

    public function tearDown(): void
    {
        self::recursiveRemoveDirectory($this->config['queueFolder']);
        if ($this->unlockInTeardown != null)
            flock($this->unlockInTeardown, LOCK_UN);
    }

    public function testSend()
    {
        $this->mailer
            ->setTo('foo@example.com', 'Foo Bar')
            ->deliver('Hello world');

        $this->assertTrue(\file_exists($this->config['queueFolder']));
        $files = scandir($this->config['queueFolder']);
        $this->assertEquals(3, \sizeof($files)); // 3 = . .. file
    }

    public function testFlushThrowsFlushException()
    {
        $this->mailer
            ->setTo('foo@example.com', 'Foo Bar')
            ->deliver('Hello world');

        $lockfile = $this->config['queueFolder'] . DS . 'flush.lock';
        touch($lockfile);
        $fh = fopen($lockfile, 'r');

        $this->assertTrue(flock($fh, LOCK_EX | LOCK_NB));
        $this->unlockInTeardown = $fh;

        $this->expectException(\MailQueue\Mailer\Transport\FlushException::class);
        $this->mailer->getTransport()->flush($this->mTransport, true);
    }

    public function testFlushRequeue()
    {
        $this->mTransport->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('bar')));

        $originalFile = $this->mailer
            ->setTo('foo@example.com', 'Foo Bar')
            ->deliver('Hello world')
            ['filename'];

        $contents = \file_get_contents($originalFile);
        $this->mailer->getTransport()->flush($this->mTransport);
        $this->assertFalse(\file_exists($originalFile));

        $iterator = $this->folder();
        while (!$iterator->current()->isFile() || $iterator->current()->getFilename() == 'flush.lock')
            $iterator->next();
        $newFile = $iterator->current();
        $newContents = \file_get_contents($newFile->getPathname());
        $this->assertEquals($contents, $newContents);
    }

    private function folder()
    {
        return new DirectoryIterator($this->config['queueFolder']);
    }

    private static function recursiveRemoveDirectory($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($dir);
    }
}