<?php

// Polyfill stub for static analyzers: define the interface only when it doesn't exist at runtime.
// This file will not conflict with the real Symfony interface because the declaration is
// guarded with interface_exists().

namespace Symfony\Component\PasswordHasher\Hasher;

if (!interface_exists(UserPasswordHasherInterface::class)) {
    interface UserPasswordHasherInterface
    {
        /**
         * Hash a plain password for a user object.
         * @param object $user
         * @param string $plainPassword
         * @return string
         */
        public function hashPassword(object $user, string $plainPassword): string;
    }
}
