<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Test\Unit\View\Template;

use PHPUnit\Framework\TestCase;

class ProductHeaderTest extends TestCase
{
    public function testDccConfigIsRenderedAsJsonString()
    {
        $json = '{"catalogData":{"locale":"en_US","catalogProducts":[{"id":"1"}]}}';
        $rrJs = "$('#tab-reviews').trigger('click');";
        $qaJs = "$('#tab-questions').trigger('click');";

        $configProvider = new class ($rrJs, $qaJs) {
            private $rrJs;
            private $qaJs;

            public function __construct($rrJs, $qaJs)
            {
                $this->rrJs = $rrJs;
                $this->qaJs = $qaJs;
            }

            public function getRrDoShowContentJs()
            {
                return $this->rrJs;
            }

            public function getQaDoShowContentJs()
            {
                return $this->qaJs;
            }
        };

        $viewModel = new class ($json, $configProvider) {
            private $json;
            private $configProvider;

            public function __construct($json, $configProvider)
            {
                $this->json = $json;
                $this->configProvider = $configProvider;
            }

            public function isBvEnabled()
            {
                return true;
            }

            public function getProduct()
            {
                return new \stdClass();
            }

            public function getExtensionInjectionMessage()
            {
                return 'injected';
            }

            public function isConfigurable()
            {
                return false;
            }

            public function isDccEnabled()
            {
                return true;
            }

            public function getDccConfig()
            {
                return $this->json;
            }

            public function canShowDebugDetails()
            {
                return false;
            }

            public function getConfigProvider()
            {
                return $this->configProvider;
            }
        };

        $block = new class ($viewModel) {
            private $viewModel;

            public function __construct($viewModel)
            {
                $this->viewModel = $viewModel;
            }

            public function getViewModel()
            {
                return $this->viewModel;
            }
        };

        $escaper = new class {
            public function escapeHtml($value)
            {
                return htmlspecialchars((string)$value);
            }
        };

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            include $this->getTemplatePath();
            $output = ob_get_clean();
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }

        $this->assertStringContainsString('window.bvDCC = ' . $json . ';', $output);
        $this->assertStringContainsString($rrJs, $output);
        $this->assertStringContainsString($qaJs, $output);
        $this->assertStringNotContainsString('&#039;#tab-reviews&#039;', $output);
        $this->assertStringNotContainsString('&#039;#tab-questions&#039;', $output);
    }

    /**
     * @return string
     */
    private function getTemplatePath()
    {
        return realpath(__DIR__ . '/../../../../view/frontend/templates/productheader.phtml');
    }
}
