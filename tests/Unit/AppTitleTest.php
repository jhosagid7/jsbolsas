<?php

namespace Tests\Unit;

use Tests\TestCase;

class AppTitleTest extends TestCase
{
    /** @test */
    public function it_shares_dynamic_system_title_and_version_correctly()
    {
        $rawVer = file_exists(base_path('version.txt')) ? trim(file_get_contents(base_path('version.txt'))) : '1.0';
        $expectedVer = 'v' . ltrim($rawVer, 'v');
        $expectedTitle = 'JSBolsas Pro ' . $expectedVer;

        $this->assertEquals($expectedTitle, config('app.name'));
    }
}