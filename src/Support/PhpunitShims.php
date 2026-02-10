<?php

namespace PHPUnit\Framework;

// Guard against PHPUnit 10+ where TestListener* was removed.
if (!\interface_exists(TestListener::class, true)) {
    interface TestListener
    {
        // Empty shim for compatibility with Mockery's TestListener.
    }
}

if (!\trait_exists(TestListenerDefaultImplementation::class, true)) {
    trait TestListenerDefaultImplementation
    {
        // Empty shim for compatibility with Mockery's TestListener.
    }
}
