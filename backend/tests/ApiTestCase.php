<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Tests run against the same database as `dev` (see README caveats), so each
// test wraps itself in a transaction that's rolled back afterward instead of
// leaving data behind or requiring a fixture reload between runs.
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        // The client boots the kernel, so it must be created before
        // getContainer() (which would otherwise boot it first and make the
        // later createClient() call fail with "already booted").
        $this->client = static::createClient();

        // By default KernelBrowser reboots the kernel (and so gets a fresh
        // EntityManager/connection) before every request, which would make
        // the transaction below invisible to requests and never rolled
        // back. Disable that so all requests in a test share one connection.
        $this->client->disableReboot();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }
}
