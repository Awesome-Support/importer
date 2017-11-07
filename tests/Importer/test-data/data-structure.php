<?php

namespace Pressware\AwesomeSupport\Tests\Importer;

use DateTime;
use Pressware\AwesomeSupport\Entity\User;
use Pressware\AwesomeSupport\Entity\Ticket;
use Pressware\AwesomeSupport\Constant\Status;
use Pressware\AwesomeSupport\Constant\UserRoles;

$agent = new User(
    'agent@agent.com',
    'AgentFirst',
    'AgentLast',
    UserRoles::AGENT
);

$customer = new User(
    'customer@customer.com',
    'CustomerFirst',
    'CustomerLast',
    UserRoles::CUSTOMER
);

return [
    new Ticket(
        $agent,
        $customer,
        'ZenDesk API',
        'This is the title',
        'This is the description',
        [
            [
                'url'      => 'http://www.learningspy.co.uk/wp-content/uploads/2016/05/Testing_in_Progress.gif',
                'filename' => 'Testing_in_Progress.gif',
            ],
            [
                'url'      => 'http://www.learningspy.co.uk/wp-content/uploads/2016/05/Testing_in_Progress.gif',
                'filename' => 'Testing_in_Progress.gif',
            ],
        ],
        [
            [
                'user'        => $agent,
                'reply'       => 'First Reply',
                'date'        => (new DateTime('-10 weeks'))->format('Y-m-d H:i:s'),
                'read'        => true,
                'attachments' => [
                    [
                        'url' => 'http://www.learningspy.co.uk/wp-content/uploads/2016/05/Testing_in_Progress.gif',
                    ],
                    [
                        'url' => 'http://www.learningspy.co.uk/wp-content/uploads/2016/05/Testing_in_Progress.gif',
                    ],
                ],
            ],
            [
                'user'        => $customer,
                'reply'       => 'Second Reply',
                'date'        => (new DateTime('-15 weeks'))->format('Y-m-d H:i:s'),
                'read'        => false,
                'attachments' => [
                    [
                        'url' => 'http://www.learningspy.co.uk/wp-content/uploads/2016/05/Testing_in_Progress.gif',
                    ],
                    [
                        'url' => 'http://www.learningspy.co.uk/wp-content/uploads/2016/05/Testing_in_Progress.gif',
                    ],
                ],
            ],
        ],
        [
            [
                'value' => Status::PROCESSING,
                'date'  => (new DateTime('-10 weeks'))->format('Y-m-d H:i:s'),
                'user'  => $agent,
            ],
            [
                'value' => Status::CLOSED,
                'date'  => (new DateTime('-5 weeks'))->format('Y-m-d H:i:s'),
                'user'  => $customer,
            ],
            [
                'value' => Status::HOLD,
                'date'  => (new DateTime('-10 weeks'))->format('Y-m-d H:i:s'),
                'user'  => $agent,
            ],
            [
                'value' => Status::OPEN,
                'date'  => (new DateTime('-5 weeks'))->format('Y-m-d H:i:s'),
                'user'  => $customer,
            ],
            [
                'value' => Status::CLOSED,
                'date'  => (new DateTime('now'))->format('Y-m-d H:i:s'),
                'user'  => $agent,
            ],
        ]
    ),
];
