<?php

namespace battye\cmxquiz\acp;

class quiz_info
{
    public function module()
    {
        return [
            'filename'  => '\battye\cmxquiz\acp\quiz_module',
            'title'     => 'ACP_CMX_QUIZ',
            'modes'    => [
                'settings'  => [
                    'title' => 'ACP_CMX_QUIZ_CONFIGURATION',
                    'auth'  => 'ext_battye/cmxquiz && acl_a_board',
                    'cat'   => ['ACP_CAT_DOT_MODS'],
                ],
            ],
        ];
    }
}