<?php

namespace App\Notify\Textmagic\ClientTests;

require_once '../Services/TextmagicRestClient.php';

use App\Notify\Textmagic\Services\TextmagicRestClient;

class TextmagicRestClientBulksTest extends \PHPUnit_Framework_TestCase
{
    public function getClient($http)
    {
        return new TextmagicRestClient('<USERNAME>', '<APIV2_TOKEN>', 'v2', $http);
    }

    public function createMockHttp($method, $url, $params, $response, $status = 200)
    {
        $http = $this->getMock('HttpCurl', ['authenticate', 'POST', 'PUT', 'DELETE', 'GET']);

        if (strtoupper($method) === 'GET') {
            $http->expects($this->once())->method('GET')
                ->with(
                    $url,
                    [],
                    http_build_query($params)
                )
                ->will(
                    $this->returnValue(
                        [
                            $status,
                            ['Content-Type' => 'application/json'],
                            json_encode($response),
                        ]
                    )
                );
        } else {
            $http->expects($this->once())->method(strtoupper($method))
                ->with(
                    $url,
                    ['Content-Type' => 'application/x-www-form-urlencoded'],
                    http_build_query($params)
                )
                ->will(
                    $this->returnValue(
                        [
                            $status,
                            ['Content-Type' => 'application/json'],
                            json_encode($response),
                        ]
                    )
                );
        }

        return $http;
    }

    public function test_bulk()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'status' => 'c',
            'itemsProcessed' => 9937,
            'itemsTotal' => 9937,
            'createdAt' => '2014-12-14T04:34:46+0000',
            'session' => [
                'id' => 1,
                'startTime' => '2014-12-14T04:34:53+0000',
                'text' => 'test',
                'source' => 'O',
                'referenceId' => 'O_user_098f6bcd4621d373cade4e832627b4f6_1414151612548d136b600eb4.33276307',
                'price' => 393.712,
                'numbersCount' => 9937,
            ],
            'text' => 'TEST',
        ];

        $http = $this->createMockHttp(
            'GET',
            'bulks/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->bulks->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_bulks()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'status' => 'c',
                    'itemsProcessed' => 9937,
                    'itemsTotal' => 9937,
                    'createdAt' => '2014-12-14T04:34:46+0000',
                    'session' => [
                        'id' => 1,
                        'startTime' => '2014-12-14T04:34:53+0000',
                        'text' => 'test',
                        'source' => 'O',
                        'referenceId' => 'O_user_098f6bcd4621d373cade4e832627b4f6_1414151612548d136b600eb4.33276307',
                        'price' => 393.712,
                        'numbersCount' => 9937,
                    ],
                    'text' => 'TEST',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'bulks',
            $params,
            $response
        );
        $result = $this->getClient($http)->bulks->getList();
        $this->assertEquals($response, $result);
    }

    public function test_chat()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'direction' => 'o',
                    'sender' => '447937946426',
                    'messageTime' => '2014-08-07T09:43:50+0000',
                    'text' => 'TEST',
                    'receiver' => '3725034224',
                    'deleted' => null,
                    'userId' => 1,
                    'status' => 'f',
                    'total' => null,
                    'firstName' => 'TEST',
                    'lastName' => 'Last TMM Test',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'chats/1234567890',
            $params,
            $response
        );
        $result = $this->getClient($http)->chats->get(1234567890);
        $this->assertEquals($response, $result);
    }

    public function test_chats()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'phone' => '1234567890',
                    'contact' => null,
                    'unread' => '0',
                    'updatedAt' => '2015-04-08T11:58:49+0000',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'chats',
            $params,
            $response
        );
        $result = $this->getClient($http)->chats->getList();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_lists_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'name' => 'TEST',
                    'description' => null,
                    'membersCount' => 7,
                    'shared' => true,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'lists',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->getList();
        $this->assertEquals($response, $result);
    }

    public function test_search_lists()
    {
        $params = [
            'ids' => 1,
        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'name' => 'TEST',
                    'description' => null,
                    'membersCount' => 7,
                    'shared' => true,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'lists/search',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->search($params);
        $this->assertEquals($response, $result);
    }

    public function test_create_list()
    {
        $params = [
            'name' => 'API TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/lists/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'lists',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_list()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'name' => 'TEST',
            'description' => null,
            'membersCount' => 7,
            'shared' => true,
        ];

        $http = $this->createMockHttp(
            'GET',
            'lists/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_update_list()
    {
        $params = [
            'name' => 'API TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/lists/1',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'lists/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->update(1, $params);
        $this->assertEquals($response, $result);
    }

    public function test_create_contact()
    {
        $params = [
            'firstName' => 'API TEST',
            'phone' => '1234567890',
            'lists' => 1,
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/lists/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'contacts',
            $params,
            $response
        );
        $result = $this->getClient($http)->contacts->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_update_list_contacts()
    {
        $params = [
            'contacts' => 1,
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/lists/1',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'lists/1/contacts',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->updateContacts(1, $params);
        $this->assertEquals($response, $result);
    }

    public function test_delete_list_contacts()
    {
        $params = [
            'contacts' => 1,
        ];

        $http = $this->createMockHttp(
            'DELETE',
            'lists/1/contacts',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->lists->deleteContacts(1, $params);
        $this->assertEquals(true, $result);
    }

    public function test_retrieve_lists_contacts()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'firstName' => 'TEST',
                    'lastName' => 'TEST',
                    'companyName' => 'TEST',
                    'phone' => '1234567890',
                    'email' => null,
                    'country' => [
                        'id' => 'RU',
                        'name' => 'Russia',
                    ],
                    'customFields' => [],
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'lists/1/contacts',
            $params,
            $response
        );
        $result = $this->getClient($http)->lists->getContacts(1);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_contacts_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'firstName' => 'TEST',
                    'lastName' => 'TEST',
                    'companyName' => 'TEST',
                    'phone' => '1234567890',
                    'email' => null,
                    'country' => [
                        'id' => 'GB',
                        'name' => 'Great Britain',
                    ],
                    'customFields' => [],
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'contacts',
            $params,
            $response
        );
        $result = $this->getClient($http)->contacts->getList();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_contact()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'firstName' => 'TEST',
            'lastName' => 'TEST',
            'companyName' => 'TEST',
            'phone' => '1234567890',
            'email' => null,
            'country' => [
                'id' => 'GB',
                'name' => 'Great Britain',
            ],
            'customFields' => [],
        ];

        $http = $this->createMockHttp(
            'GET',
            'contacts/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->contacts->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_update_contact()
    {
        $params = [
            'firstName' => 'API TEST',
            'phone' => '1234567890',
            'lists' => 1,
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/contacts/1',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'contacts/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->contacts->update(1, $params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_custom_fields_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'name' => 'year',
                    'createdAt' => null,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'customfields',
            $params,
            $response
        );
        $result = $this->getClient($http)->customfields->getList();
        $this->assertEquals($response, $result);
    }

    public function test_create_custom_field()
    {
        $params = [
            'name' => 'API TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/customfields/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'customfields',
            $params,
            $response
        );
        $result = $this->getClient($http)->customfields->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_custom_field()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'name' => 'year',
            'createdAt' => null,
        ];

        $http = $this->createMockHttp(
            'GET',
            'customfields/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->customfields->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_update_custom_field()
    {
        $params = [
            'name' => 'API TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/customfields/1',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'customfields/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->customfields->update(1, $params);
        $this->assertEquals($response, $result);
    }

    public function test_update_custom_field_contact()
    {
        $params = [
            'contactId' => 1,
            'value' => 'API TEST VALUE',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/customfields/1',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'customfields/1/update',
            $params,
            $response
        );
        $result = $this->getClient($http)->customfields->updateContact(1, $params);
        $this->assertEquals($response, $result);
    }

    public function test_delete_custom_field()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'customfields/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->customfields->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_delete_contact()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'contacts/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->contacts->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_retrieve_messages_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'receiver' => '1234567890',
                    'messageTime' => '2015-06-19T09:09:08+0000',
                    'status' => 'j',
                    'text' => 'AAAA',
                    'charset' => 'ISO-8859-1',
                    'firstName' => null,
                    'lastName' => null,
                    'country' => 'UA',
                    'sender' => '447624800500',
                    'price' => 0,
                    'partsCount' => 1,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'messages',
            $params,
            $response
        );
        $result = $this->getClient($http)->messages->getList();
        $this->assertEquals($response, $result);
    }

    public function test_search_messages()
    {
        $params = [
            'ids' => 1,
        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'receiver' => '1234567890',
                    'messageTime' => '2015-06-19T09:09:08+0000',
                    'status' => 'j',
                    'text' => 'AAAA',
                    'charset' => 'ISO-8859-1',
                    'firstName' => null,
                    'lastName' => null,
                    'country' => 'GB',
                    'sender' => '447624800500',
                    'price' => 0,
                    'partsCount' => 1,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'messages/search',
            $params,
            $response
        );
        $result = $this->getClient($http)->messages->search($params);
        $this->assertEquals($response, $result);
    }

    public function test_message_price()
    {
        $params = [
            'text' => 'API TEST',
            'phones' => '1234567890',
        ];
        $response = [
            'total' => 0.049,
            'parts' => 1,
            'countries' => [
                'IM' => [
                    'country' => 'IM',
                    'count' => 1,
                    'max' => 0.049,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'messages/price',
            $params,
            $response
        );
        $result = $this->getClient($http)->messages->getPrice($params);
        $this->assertEquals($response, $result);
    }

    public function test_create_message()
    {
        $params = [
            'text' => 'API TEST',
            'phones' => '1234567890',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/messages/1',
            'type' => 'message',
            'sessionId' => 1,
            'bulkId' => null,
            'messageId' => 1,
            'scheduleId' => null,
        ];

        $http = $this->createMockHttp(
            'POST',
            'messages',
            $params,
            $response
        );
        $result = $this->getClient($http)->messages->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_sessions_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'startTime' => '2014-12-14T04:34:53+0000',
                    'text' => 'test',
                    'source' => 'O',
                    'referenceId' => 'O_user_098f6bcd4621d373cade4e832627b4f6_1414151612548d136b600eb4.33276307',
                    'price' => 393.712,
                    'numbersCount' => 9937,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'sessions',
            $params,
            $response
        );
        $result = $this->getClient($http)->sessions->getList();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_session()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'startTime' => '2014-12-14T04:34:53+0000',
            'text' => 'test',
            'source' => 'O',
            'referenceId' => 'O_user_098f6bcd4621d373cade4e832627b4f6_1414151612548d136b600eb4.33276307',
            'price' => 393.712,
            'numbersCount' => 9937,
        ];

        $http = $this->createMockHttp(
            'GET',
            'sessions/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->sessions->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_session_messages()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'receiver' => '1234567890',
                    'messageTime' => '2015-06-19T09:09:08+0000',
                    'status' => 'j',
                    'text' => 'AAAA',
                    'charset' => 'ISO-8859-1',
                    'firstName' => null,
                    'lastName' => null,
                    'country' => 'UA',
                    'sender' => '447624800500',
                    'price' => 0,
                    'partsCount' => 1,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'sessions/1/messages',
            $params,
            $response
        );
        $result = $this->getClient($http)->sessions->getMessages(1);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_message()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'receiver' => '1234567890',
            'messageTime' => '2015-06-19T09:09:08+0000',
            'status' => 'j',
            'text' => 'AAAA',
            'charset' => 'ISO-8859-1',
            'firstName' => null,
            'lastName' => null,
            'country' => 'UA',
            'sender' => '447624800500',
            'price' => 0,
            'partsCount' => 1,
        ];

        $http = $this->createMockHttp(
            'GET',
            'messages/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->messages->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_delete_message()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'messages/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->messages->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_delete_session()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'sessions/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->sessions->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_retrieve_replies_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 5946226,
                    'sender' => '3725034224',
                    'messageTime' => '2014-08-07T15:45:29+0000',
                    'text' => 'TEST',
                    'receiver' => '447520615172',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'replies',
            $params,
            $response
        );
        $result = $this->getClient($http)->replies->getList();
        $this->assertEquals($response, $result);
    }

    public function test_search_replies()
    {
        $params = [
            'ids' => 1,
        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 5946226,
                    'sender' => '3725034224',
                    'messageTime' => '2014-08-07T15:45:29+0000',
                    'text' => 'TEST',
                    'receiver' => '447520615172',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'replies/search',
            $params,
            $response
        );
        $result = $this->getClient($http)->replies->search($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_reply()
    {
        $params = [

        ];
        $response = [
            'id' => 5946226,
            'sender' => '3725034224',
            'messageTime' => '2014-08-07T15:45:29+0000',
            'text' => 'TEST',
            'receiver' => '447520615172',
        ];

        $http = $this->createMockHttp(
            'GET',
            'replies/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->replies->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_delete_reply()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'replies/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->replies->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_retrieve_schedules_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'nextSend' => '2016-06-10T08:12:08+0000',
                    'session' => [
                        'id' => 1,
                        'startTime' => '2016-06-10T08:12:08+0000',
                        'text' => 'TEST',
                        'source' => 'A',
                        'referenceId' => 'A_user_c72b9698fa1927e1dd12d3cf26ed84b2_15556075085577f18a648910.60189442',
                        'price' => 0,
                        'numbersCount' => 1,
                    ],
                    'rrule' => null,
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'schedules',
            $params,
            $response
        );
        $result = $this->getClient($http)->schedules->getList();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_schedule()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'nextSend' => '2016-06-10T08:12:08+0000',
            'session' => [
                'id' => 1,
                'startTime' => '2016-06-10T08:12:08+0000',
                'text' => 'TEST',
                'source' => 'A',
                'referenceId' => 'A_user_c72b9698fa1927e1dd12d3cf26ed84b2_15556075085577f18a648910.60189442',
                'price' => 0,
                'numbersCount' => 1,
            ],
            'rrule' => null,
        ];

        $http = $this->createMockHttp(
            'GET',
            'schedules/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->schedules->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_delete_schedule()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'schedules/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->schedules->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_user_messaging_stats()
    {
        $params = [

        ];
        $response = [
            [
                'replyRate' => 0,
                'date' => null,
                'deliveryRate' => null,
                'costs' => null,
                'messagesReceived' => null,
                'messagesSentDelivered' => null,
                'messagesSentAccepted' => null,
                'messagesSentBuffered' => null,
                'messagesSentFailed' => null,
                'messagesSentRejected' => null,
                'messagesSentParts' => null,
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'stats/messaging',
            $params,
            $response
        );
        $result = $this->getClient($http)->stats->messaging();
        $this->assertEquals($response, $result);
    }

    public function test_user_spending_stats()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'userId' => null,
                    'date' => '2015-06-19T09:09:11+0000',
                    'balance' => 5272.59,
                    'delta' => 0.074,
                    'type' => 'rejected',
                    'value' => 49575945,
                    'comment' => 'Rejected message to unsubscribed contact',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'stats/spending',
            $params,
            $response
        );
        $result = $this->getClient($http)->stats->spending();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_templates_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'name' => 'TEST',
                    'content' => 'TEST',
                    'lastModified' => '2015-05-25T05:31:57+0000',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'templates',
            $params,
            $response
        );
        $result = $this->getClient($http)->templates->getList();
        $this->assertEquals($response, $result);
    }

    public function test_search_templates()
    {
        $params = [
            'name' => 'TEST',
        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'name' => 'TEST',
                    'content' => 'TEST',
                    'lastModified' => '2015-05-25T05:31:57+0000',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'templates/search',
            $params,
            $response
        );
        $result = $this->getClient($http)->templates->search($params);
        $this->assertEquals($response, $result);
    }

    public function test_create_template()
    {
        $params = [
            'name' => 'API TEST',
            'content' => 'API TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/templates/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'templates',
            $params,
            $response
        );
        $result = $this->getClient($http)->templates->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_template()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'name' => 'TEST',
            'content' => 'TEST',
            'lastModified' => '2015-05-25T05:31:57+0000',
        ];

        $http = $this->createMockHttp(
            'GET',
            'templates/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->templates->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_update_template()
    {
        $params = [
            'name' => 'API TEST',
            'content' => 'API TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/templates/1',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'templates/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->templates->update(1, $params);
        $this->assertEquals($response, $result);
    }

    public function test_delete_template()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'templates/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->templates->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_retrieve_unsubscribers_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'phone' => '1234567890',
                    'unsubscribeTime' => '2014-12-10T07:20:59+0000',
                    'firstName' => 'Test',
                    'lastName' => 'Test',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'unsubscribers',
            $params,
            $response
        );
        $result = $this->getClient($http)->unsubscribers->getList();
        $this->assertEquals($response, $result);
    }

    public function test_create_unsubscriber()
    {
        $params = [
            'phone' => '1234567890',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/unsubscribers/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'unsubscribers',
            $params,
            $response
        );
        $result = $this->getClient($http)->unsubscribers->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_unsubscriber()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'phone' => '1234567890',
            'unsubscribeTime' => '2014-12-10T07:20:59+0000',
            'firstName' => 'Test',
            'lastName' => 'Test',
        ];

        $http = $this->createMockHttp(
            'GET',
            'unsubscribers/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->unsubscribers->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_user()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'username' => 'TEST',
            'firstName' => 'TEST',
            'lastName' => 'TEST',
            'balance' => 5272.59,
            'company' => 'Textmagic',
            'currency' => [
                'id' => 'EUR',
                'htmlSymbol' => '&euro;',
            ],
            'timezone' => [
                'id' => 1,
                'area' => 'UTC',
                'dst' => 0,
                'offset' => 0,
                'timezone' => 'UTC',
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'user',
            $params,
            $response
        );
        $result = $this->getClient($http)->user->get();
        $this->assertEquals($response, $result);
    }

    public function test_update_user()
    {
        $params = [
            'firstName' => 'TEST',
            'lastName' => 'TEST',
            'company' => 'TEST',
        ];
        $response = [
            'href' => '/api/v2/user',
        ];

        $http = $this->createMockHttp(
            'PUT',
            'user',
            $params,
            $response
        );
        $result = $this->getClient($http)->user->update($params);
        $this->assertEquals($response, $result);
    }

    public function test_ping_client()
    {
        $response = [
            'ping' => 'pong',
        ];

        $http = $this->createMockHttp(
            'GET',
            'ping',
            [],
            $response
        );

        $result = $this->getClient($http)->utils->ping();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_sources_list()
    {
        $params = [

        ];
        $response = [
            'dedicated' => [
                '1234567890',
            ],
            'shared' => [
                '1234567890',
            ],
            'senderIds' => [
                'TEST',
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'sources',
            $params,
            $response
        );
        $result = $this->getClient($http)->sources->getList();
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_invoices_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'bundle' => 20,
                    'currency' => 'EUR',
                    'vat' => 0,
                    'paymentMethod' => 'Visa ending in 1111',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'invoices',
            $params,
            $response
        );
        $result = $this->getClient($http)->invoices->getList();
        $this->assertEquals($response, $result);
    }

    public function test_numbers_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'user' => [
                        'id' => 1,
                        'username' => 'TEST',
                        'firstName' => 'TEST',
                        'lastName' => 'TEST',
                        'status' => 'A',
                        'balance' => 5272.54,
                        'company' => 'Textmagic',
                        'currency' => [
                            'id' => 'EUR',
                            'htmlSymbol' => '&euro',
                        ],
                        'timezone' => [
                            'id' => 1,
                            'area' => 'UTC',
                            'dst' => 0,
                            'offset' => 0,
                            'timezone' => 'UTC',
                        ],
                        'subaccountType' => 'P',
                    ],
                    'purchasedAt' => '2014-11-26T08:10:28+0000',
                    'expireAt' => '2014-12-25T08:10:28+0000',
                    'phone' => '1234567890',
                    'country' => [
                        'id' => 'GB',
                        'name' => 'Great Britain',
                    ],
                    'status' => 'A',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'numbers',
            $params,
            $response
        );
        $result = $this->getClient($http)->numbers->getList();
        $this->assertEquals($response, $result);
    }

    public function test_numbers_available()
    {
        $params = [
            'country' => 'GB',
        ];
        $response = [
            'numbers' => [
                '1234567890',
            ],
            'price' => 3,
        ];

        $http = $this->createMockHttp(
            'GET',
            'numbers/available',
            $params,
            $response
        );
        $result = $this->getClient($http)->numbers->getAvailable($params);
        $this->assertEquals($response, $result);
    }

    public function test_buy_number()
    {
        $params = [
            'phone' => '1234567890',
            'country' => 'GB',
            'userId' => '1',

        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/numbers/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'numbers',
            $params,
            $response
        );
        $result = $this->getClient($http)->numbers->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_number()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'user' => [
                'id' => 1,
                'username' => 'TEST',
                'firstName' => 'TEST',
                'lastName' => 'TEST',
                'status' => 'A',
                'balance' => 5272.54,
                'company' => 'Textmagic',
                'currency' => [
                    'id' => 'EUR',
                    'htmlSymbol' => '&euro',
                ],
                'timezone' => [
                    'id' => 1,
                    'area' => 'UTC',
                    'dst' => 0,
                    'offset' => 0,
                    'timezone' => 'UTC',
                ],
                'subaccountType' => 'P',
            ],
            'purchasedAt' => '2014-11-26T08:10:28+0000',
            'expireAt' => '2014-12-25T08:10:28+0000',
            'phone' => '1234567890',
            'country' => [
                'id' => 'GB',
                'name' => 'Great Britain',
            ],
            'status' => 'A',
        ];

        $http = $this->createMockHttp(
            'GET',
            'numbers/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->numbers->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_delete_number()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'numbers/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->numbers->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_sender_id_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'senderId' => 'TEST',
                    'user' => [
                        'id' => 1,
                        'username' => 'TEST',
                        'firstName' => 'TEST',
                        'lastName' => 'TEST',
                        'status' => 'A',
                        'balance' => 5272.54,
                        'company' => 'Textmagic',
                        'currency' => [
                            'id' => 'EUR',
                            'htmlSymbol' => '&euro',
                        ],
                        'timezone' => [
                            id => 1,
                            'area' => 'UTC',
                            'dst' => 0,
                            'offset' => 0,
                            'timezone' => 'UTC',
                        ],
                        'subaccountType' => 'P',
                    ],
                    'status' => 'A',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'senderids',
            $params,
            $response
        );
        $result = $this->getClient($http)->senderids->getList();
        $this->assertEquals($response, $result);
    }

    public function test_create_sender_id()
    {
        $params = [
            'senderId' => 'TEST',
            'explanation' => 'TEST',
        ];
        $response = [
            'id' => 1,
            'href' => '/api/v2/senderids/1',
        ];

        $http = $this->createMockHttp(
            'POST',
            'senderids',
            $params,
            $response
        );
        $result = $this->getClient($http)->senderids->create($params);
        $this->assertEquals($response, $result);
    }

    public function test_retrieve_sender_id()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'user' => [
                'id' => 1,
                'username' => 'TEST',
                'firstName' => 'TEST',
                'lastName' => 'TEST',
                'status' => 'A',
                'balance' => 5272.54,
                'company' => 'Textmagic',
                'currency' => [
                    'id' => 'EUR',
                    'htmlSymbol' => '&euro',
                ],
                'timezone' => [
                    'id' => 1,
                    'area' => 'UTC',
                    'dst' => 0,
                    'offset' => 0,
                    'timezone' => 'UTC',
                ],
                'subaccountType' => 'P',
            ],
            'status' => 'A',
        ];

        $http = $this->createMockHttp(
            'GET',
            'senderids/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->senderids->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_delete_sender_id()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'senderids/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->senderids->delete(1);
        $this->assertEquals(true, $result);
    }

    public function test_subaccount_list()
    {
        $params = [

        ];
        $response = [
            'page' => 1,
            'limit' => 10,
            'pageCount' => 1,
            'resources' => [
                [
                    'id' => 1,
                    'username' => 'TEST',
                    'firstName' => 'TEST',
                    'lastName' => 'TEST',
                    'status' => 'T',
                    'balance' => 5266.54,
                    'company' => '',
                    'currency' => [
                        'id' => 'EUR',
                        'htmlSymbol' => '&euro;',
                    ],
                    'timezone' => [
                        'id' => 1,
                        'area' => 'GMT',
                        'dst' => 0,
                        'offset' => 0,
                        'timezone' => 'GMT',
                    ],
                    'subaccountType' => 'U',
                ],
            ],
        ];

        $http = $this->createMockHttp(
            'GET',
            'subaccounts',
            $params,
            $response
        );
        $result = $this->getClient($http)->subaccounts->getList();
        $this->assertEquals($response, $result);
    }

    public function test_create_subaccount()
    {
        $params = [
            'email' => 'test@test.com',
            'role' => 'A',
        ];

        $http = $this->createMockHttp(
            'POST',
            'subaccounts',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->subaccounts->create($params);
        $this->assertEquals(true, $result);
    }

    public function test_retrieve_subaccount()
    {
        $params = [

        ];
        $response = [
            'id' => 1,
            'username' => 'TEST',
            'firstName' => 'TEST',
            'lastName' => 'TEST',
            'status' => 'T',
            'balance' => 5266.54,
            'company' => '',
            'currency' => [
                'id' => 'EUR',
                'htmlSymbol' => '&euro;',
            ],
            'timezone' => [
                'id' => 1,
                'area' => 'GMT',
                'dst' => 0,
                'offset' => 0,
                'timezone' => 'GMT',
            ],
            'subaccountType' => 'U',
        ];

        $http = $this->createMockHttp(
            'GET',
            'subaccounts/1',
            $params,
            $response
        );
        $result = $this->getClient($http)->subaccounts->get(1);
        $this->assertEquals($response, $result);
    }

    public function test_delete_subaccount()
    {
        $params = [

        ];

        $http = $this->createMockHttp(
            'DELETE',
            'subaccounts/1',
            $params,
            null,
            204
        );
        $result = $this->getClient($http)->subaccounts->delete(1);
        $this->assertEquals(true, $result);
    }
}
