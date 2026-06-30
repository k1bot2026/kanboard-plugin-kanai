<?php

// Standalone test bootstrap — loads Composer autoload only.
// Pure-logic KanAI classes (Security\Crypto, proposal validation, context
// formatting/truncation helpers) must NOT depend on Kanboard core, so they are
// testable here without a running Kanboard.
require __DIR__ . '/../vendor/autoload.php';
