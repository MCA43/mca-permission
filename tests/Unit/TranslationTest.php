<?php

namespace Mca\Permission\Tests\Unit;

use Mca\Permission\Tests\TestCase;

class TranslationTest extends TestCase
{
    public function test_mca_perm_returns_english_by_default_in_tests(): void
    {
        $this->assertSame('Permissions', mca_perm('nav.permissions'));
        $this->assertSame('Save', mca_perm('common.save'));
    }

    public function test_mca_perm_supports_turkish_locale(): void
    {
        app()->setLocale('tr');

        $this->assertSame('İzinler', mca_perm('nav.permissions'));
        $this->assertSame('Kaydet', mca_perm('common.save'));
    }
}
