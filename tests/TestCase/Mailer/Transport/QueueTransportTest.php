<?php

namespace MailQueue\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;

use MailQueue\Mailer\Transport\QueueTransport;

/**
 * @property \Cake\Mailer\Email email
 */
class QueueTransportTest extends TestCase
{
    private $email;
    private $config;
    private $unlockInTeardown;
    private $mTransport;

    public function setUp()
    {
        $this->email = new Email('default');
        $this->config = $this->email->getTransport()->getConfig(null);
        $this->mTransport = $this->getMockBuilder(\Cake\Mailer\AbstractTransport::class)
            ->setMethods(['send'])
            ->getMock();
    }

    public function tearDown()
    {
        $this->folder()->delete();
        if ($this->unlockInTeardown != null)
            flock($this->unlockInTeardown, LOCK_UN);
    }

    public function testSend()
    {
        $this->email
            ->setTo('foo@example.com', 'Foo Bar')
            ->send('Hello world');

        $this->assertTrue(\file_exists($this->config['queueFolder']));
        $files = scandir($this->config['queueFolder']);
        $this->assertEquals(3, \sizeof($files)); // 3 = . .. file
    }

    public function testFlushThrowsFlushException()
    {
        $this->email
            ->setTo('foo@example.com', 'Foo Bar')
            ->send('Hello world');

        $lockfile = $this->folder()->realPath('flush.lock');
        touch($lockfile);
        $fh = fopen($lockfile, 'r');

        $this->assertTrue(flock($fh, LOCK_EX | LOCK_NB));
        $this->unlockInTeardown = $fh;

        $this->expectException(\MailQueue\Mailer\Transport\FlushException::class);
        $this->email->getTransport()->flush($this->mTransport, true);
    }

    public function testFlushRequeue()
    {
        $this->mTransport->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('bar')));

        $originalFile = $this->email
            ->setTo('foo@example.com', 'Foo Bar')
            ->send('Hello world')
            ['filename'];

        $contents = \file_get_contents($originalFile);
        $this->email->getTransport()->flush($this->mTransport);
        $this->assertFalse(\file_exists($originalFile));
        
        $newFile = $this->folder()->find()[0];
        $newContents = \file_get_contents($this->folder()->realpath($newFile));
        $this->assertEquals($contents, $newContents);
    }

    /**
     * @return \Cake\Filesystem\Folder
     */
    private function folder()
    {
        return new \Cake\Filesystem\Folder($this->config['queueFolder']);
    }
}