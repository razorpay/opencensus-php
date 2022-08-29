<?php

namespace RZP\Services\Mock;

use RZP\Services\FreshdeskTicketClient as BaseFreshdeskTicketClient;

class FreshdeskTicketClient extends BaseFreshdeskTicketClient
{
    protected  function makeRequestAndGetStatus(string $method, string $url, string $auth, array $content)
    {
        return [
            'status' => 2,
            'id'     => 1,
        ];
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param array $queryParams
     * @param string $urlKey
     * @param string $authKey
     * @return array $response
     */
    public function getTickets(array $queryParams, string $urlKey = 'urlind', string $authKey = 'token') : array
    {
        $response = [
            'results' => [
                [
                    'cc_emails'=> [],
                    'fwd_emails'=> [],
                    'reply_cc_emails'=> [],
                    'ticket_cc_emails'=> [],
                    'fr_escalated'=> false,
                    'spam'=> false,
                    'email_config_id'=> null,
                    'group_id'=> 11000003635,
                    'priority'=> 1,
                    'requester_id'=> 11033774580,
                    'responder_id'=> null,
                    'source'=> 2,
                    'company_id'=> null,
                    'status'=> 5,
                    'subject'=> "[Merchant] Activation",
                    'association_type'=> null,
                    'to_emails'=> null,
                    'product_id'=> null,
                    'id'=> 4478017,
                    'type'=> null,
                    'due_by'=> "2020-10-13T06:05:31Z",
                    'fr_due_by'=> "2020-10-09T06:05:31Z",
                    'is_escalated'=> false,
                    'description'=> "<div>test<br><b>Contact Number: </b>08095853344<br>\n</div>",
                    'description_text'=> "test Contact Number: 08095853344",
                    'custom_fields'=> [
                        'cf_requester_category'=> "Merchant",
                        'cf_requestor_subcategory'=> "Activation",
                        'cf_subcategory'=> "Source-Ticket form",
                        'cf_item'=> null,
                        'cf_prospect_gmv'=> null,
                        'cf_platform_item'=> null,
                        'cf_gateway'=> null,
                        'cf_csat'=> null,
                        'cf_rating'=> null,
                        'cf_automation_subcategory'=> null,
                        'cf_automation_item'=> null,
                        'cf_subreason'=> null,
                        'cf_ticket_queue'=> null,
                        'cf_merchant_id'=> "10000000000000",
                        'cf_transaction_id'=> null,
                        'cf_payment_method'=> null,
                        'cf_category'=> "Duplicate ticket",
                        'cf_chargeback_status'=> null,
                        'cf_product'=> null,
                        'cf_stop_automations'=> false,
                        'cf_automation_category'=> null,
                        'cf_platform'=> null,
                        'cf_grievance_raised_for_group'=> null,
                        'cf_grievance_original_ticket_id'=> null,
                        'cf_custom_source'=> null,
                        'cf_zendesk_ticket_id'=> null,
                        'cf_zendesk_ticket_category'=> null,
                        'cf_transfer_ticket_to_x'=> false,
                        'cf_zendesk_ticket'=> false,
                        'cf_checkout'=> null,
                        'cf_directly_closed'=> "Directly_closed",
                        'cf_recent_agent_response_time'=> null,
                        'cf_recent_customer_response_time'=> null,
                        'cf_child_ticket_status'=> null,
                        'cf_transfer_ticket_to_tech'=> false,
                        'cf_chat_initiated_url'=> null,
                        'cf_chat_interaction_id'=> null,
                        'cf_chat_resolution_time'=> null,
                        'cf_chat_conversation_id'=> null,
                        'cf_chat_1st_response_time'=> null,
                        'cf_xfreshdesk_ticket_id'=> null,
                        'cf_tech_ticket_id'=> null,
                        'cf_was_it_an_incident'=> null,
                        'cf_rca'=> null,
                        'cf_monetary_or_business_lossimpact'=> null,
                        'cf_monetary_lossimpact'=> null,
                        'cf_transfer_to_capital'=> false,
                        'cf_child_tickets_group'=> null,
                        'cf_cc_from_source'=> null,
                        'cf_transfer_group'=> null,
                        'cf_to_email'=> null,
                        'cf_pending_with'=> null,
                        'cf_reason_for_support'=> null,
                        'cf_reason_for_escalation'=> null,
                        'cf_points_from_merchant'=> null,
                        'cf_type_of_request'=> null,
                        'cf_escalation_raised_for'=> null,
                        'cf_escalated_ticket'=> null,
                        'cf_dependency'=> null,
                        'cf_previous_escalations'=> null,
                        'cf_source_of_escalation'=> null,
                        'cf_reason_for_escalation432896'=> null,
                        'cf_number_of_followers'=> null,
                        'cf_parent_ticket_id'=> null
                    ],
                    'created_at'=> "2020-10-08T11:05:31Z",
                    'updated_at'=> "2020-10-08T11:18:25Z",
                    'associated_tickets_count'=> null,
                    'tags'=> [
                        "direct_closure",
                        "Merged_ticket"
                    ],
                    'nr_due_by'=> null,
                    'nr_escalated'=> false
                ],
            ],
            'total' => 1
        ];

        return $response;
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param string $ticketId
     * @param array $queryParams
     * @param $urlKey
     * @param $authKey
     * @return array $response
     */
    public function getTicketConversations(string $ticketId, array $queryParams, $urlKey = 'urlind') : array
    {
        $response = [
            [
                'body'=> "<table style=\"margin-left: auto;margin-right: auto\">  <tbody>\n<tr>    <td>      <table style=\"margin:0px auto;background: #f4f8fa;overflow: auto;padding: 0 15px; min-width: 500px; margin: 0px\">          <tbody>\n<tr style=\"border: none\">    <td style=\"padding: 10px 20px;color: #333;color: #3f3f46\">        <div></div>\n<div style=\"padding:0 5px 0 0;font-size:12px; color: #6f7071;margin-left: 33px\">Eloquent Info Solutions Private Limited</div>\n<div></div>        <img src=\"https://images.freshchat.com/30x30/fresh-chat-names/Alphabets/E.png\" style=\"width: 30px;height: 30px;border-radius: 50% 6px 50% 50%;float:left;margin-right: 3px\">        <div style=\"border-radius: 4px 20px 20px;background: #a8ddfd;max-width: 320px;padding: 12px;float: left\"><div>please resolve</div></div>        <div style=\"color: #999999; font-size: 11px; clear: left;margin-left: 33px\">04:58 PM, 07th Jul</div>    </td>\n</tr>\n<tr></tr>\n<tr style=\"border: none\">  <td style=\"padding: 10px 20px;font-size: 13.6px;color: #3f3f46\">        <div style=\"float:right\">\n<div style=\"font-size: 12px;font-weight: 500;float: right; color: #6f7071; margin-right: 30px\">Ashmitha</div>        <img src=\"https://images.freshchat.com/30x30/fresh-chat-names/Alphabets/A.png\" style=\"width: 30px;height: 30px;border-radius: 6px 50% 50% 50%;margin-left:5px;float:right;clear:right\">        <div style=\"float: right;border-radius: 20px 4px 20px 20px;background-color: #ffffff;max-width: 320px;padding: 12px\"><div>Hi</div></div>        <div style=\"color: #999999;font-size: 11px;clear: right\">05:01 PM, 07th Jul</div>\n</div>   </td>\n</tr>\n<tr>       </tr>\n</tbody>\n</table>    </td>  </tr>  <tr>    <td>      <table style=\"margin:10px auto;padding:0 20px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#333;line-height:1.4;margin: 0px;min-width: 100%\">        <tbody>\n<tr><td style=\"text-align: center\"><span style=\"font-size: 13px; color: #999\">View conversation in <a href=\"https://web.freshchat.com/a/153370844780746/open/conversation/374169896285298\" style=\"font-weight: 500; color: #999; text-decoration: none\" rel=\"noreferrer\">Freshchat</a></span></td></tr>      </tbody>\n</table>    </td>  </tr>\n</tbody>\n</table>",
                'body_text'=> "Eloquent Info Solutions Private Limited                    please resolve           04:58 PM, 07th Jul                   Ashmitha                   Hi           05:01 PM, 07th Jul                                          View conversation in Freshchat",
                'id'=> 11119788088,
                'incoming'=> false,
                'private'=> false,
                'user_id'=> 11014228407,
                'support_email'=> null,
                'source'=> 2,
                'category'=> 3,
                'ticket_id'=> 3474137,
                'to_emails'=> [],
                'from_email'=> null,
                'cc_emails'=> [],
                'bcc_emails'=> null,
                'email_failure_count'=> null,
                'outgoing_failures'=> null,
                'created_at'=> "2020-07-07T11:58:32Z",
                'updated_at'=> "2020-07-07T11:58:32Z",
                'attachments'=> [],
                'source_additional_info'=> null
            ]
        ];

        return $response;
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param string $ticketId
     * @param $urlKey
     * @param $authKey
     * @return array $response
     */
    public function getTicketWithStats(string $ticketId, $urlKey = 'urlind', $authKey = 'token') : array
    {
        $response = [
            'cc_emails'=> [],
            'fwd_emails'=> [],
            'reply_cc_emails'=> [],
            'ticket_cc_emails'=> [],
            'fr_escalated'=> false,
            'spam'=> false,
            'email_config_id'=> null,
            'group_id'=> 11000003635,
            'priority'=> 1,
            'requester_id'=> 11033774580,
            'responder_id'=> null,
            'source'=> 2,
            'company_id'=> null,
            'status'=> 5,
            'subject'=> "[Merchant] Activation",
            'association_type'=> null,
            'to_emails'=> null,
            'product_id'=> null,
            'id'=> 4478017,
            'type'=> null,
            'due_by'=> "2020-10-13T06:05:31Z",
            'fr_due_by'=> "2020-10-09T06:05:31Z",
            'is_escalated'=> false,
            'description'=> "<div>test<br><b>Contact Number: </b>08095853344<br>\n</div>",
            'description_text'=> "test Contact Number: 08095853344",
            'custom_fields'=> [
                'cf_requester_category'=> "Merchant",
                'cf_requestor_subcategory'=> "Activation",
                'cf_subcategory'=> "Source-Ticket form",
                'cf_item'=> null,
                'cf_prospect_gmv'=> null,
                'cf_platform_item'=> null,
                'cf_gateway'=> null,
                'cf_csat'=> null,
                'cf_rating'=> null,
                'cf_automation_subcategory'=> null,
                'cf_automation_item'=> null,
                'cf_subreason'=> null,
                'cf_ticket_queue'=> null,
                'cf_merchant_id'=> "10000000000000",
                'cf_transaction_id'=> null,
                'cf_payment_method'=> null,
                'cf_category'=> "Duplicate ticket",
                'cf_chargeback_status'=> null,
                'cf_product'=> null,
                'cf_stop_automations'=> false,
                'cf_automation_category'=> null,
                'cf_platform'=> null,
                'cf_grievance_raised_for_group'=> null,
                'cf_grievance_original_ticket_id'=> null,
                'cf_custom_source'=> null,
                'cf_zendesk_ticket_id'=> null,
                'cf_zendesk_ticket_category'=> null,
                'cf_transfer_ticket_to_x'=> false,
                'cf_zendesk_ticket'=> false,
                'cf_checkout'=> null,
                'cf_directly_closed'=> "Directly_closed",
                'cf_recent_agent_response_time'=> null,
                'cf_recent_customer_response_time'=> null,
                'cf_child_ticket_status'=> null,
                'cf_transfer_ticket_to_tech'=> false,
                'cf_chat_initiated_url'=> null,
                'cf_chat_interaction_id'=> null,
                'cf_chat_resolution_time'=> null,
                'cf_chat_conversation_id'=> null,
                'cf_chat_1st_response_time'=> null,
                'cf_xfreshdesk_ticket_id'=> null,
                'cf_tech_ticket_id'=> null,
                'cf_was_it_an_incident'=> null,
                'cf_rca'=> null,
                'cf_monetary_or_business_lossimpact'=> null,
                'cf_monetary_lossimpact'=> null,
                'cf_transfer_to_capital'=> false,
                'cf_child_tickets_group'=> null,
                'cf_cc_from_source'=> null,
                'cf_transfer_group'=> null,
                'cf_to_email'=> null,
                'cf_pending_with'=> null,
                'cf_reason_for_support'=> null,
                'cf_reason_for_escalation'=> null,
                'cf_points_from_merchant'=> null,
                'cf_type_of_request'=> null,
                'cf_escalation_raised_for'=> null,
                'cf_escalated_ticket'=> null,
                'cf_dependency'=> null,
                'cf_previous_escalations'=> null,
                'cf_source_of_escalation'=> null,
                'cf_reason_for_escalation432896'=> null,
                'cf_number_of_followers'=> null,
                'cf_parent_ticket_id'=> null
            ],
            'created_at'=> "2020-10-08T11:05:31Z",
            'updated_at'=> "2020-10-08T11:18:25Z",
            'associated_tickets_count'=> null,
            'tags'=> [
                "direct_closure",
                "Merged_ticket"
            ],
            'stats' => [
                'agent_responded_at'=> "2020-07-07T11:58:32Z",
                'requester_responded_at'=> null,
                'first_responded_at'=> "2020-07-07T11:58:32Z",
                'status_updated_at'=> "2020-07-07T07:33:18Z",
                'reopened_at'=> null,
                'resolved_at'=> "2020-07-07T07:33:18Z",
                'closed_at'=> "2020-07-07T07:33:18Z",
                'pending_since'=> null
            ],
            'nr_due_by'=> null,
            'nr_escalated'=> false
        ];

        return $response;
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param string $ticketId
     * @param array $input
     * @param $urlKey
     * @param $authKey
     * @return array $response
     */
    public function postTicketReply(string $ticketId, array $input, $urlKey = 'urlind') : array
    {
        $response = [
            'id'=> 11128041196,
            'user_id'=> 11014832897,
            'from_email'=> "\"Razorpay Support\" <rzr05py08emsp@razorpay.com>",
            'cc_emails'=> [
                "akash.raina@razorpay.com"
            ],
            'bcc_emails'=> [],
            'body'=> "<div>123211 fdf3</div>",
            'body_text'=> "123211 fdf3",
            'ticket_id'=> 4382694,
            'to_emails'=> [
                "a@a.com"
            ],
            'attachments'=> [],
            'source_additional_info'=> null,
            'created_at'=> "2020-10-12T06:15:02Z",
            'updated_at'=> "2020-10-12T06:15:02Z",
        ];

        return $response;
    }

    /**
     * Create ticket
     *
     * @param array $queryParams
     * @param string $urlKey
     * @return array $response
     */
    public function postTicket(array $input, $urlKey = 'urlind') : array
    {
        $response = [
            "cc_emails" => ["support@razorpay.com"],
            "fwd_emails" => [],
            "reply_cc_emails" => ["support@razorpay.com"],
            "email_config_id" => null,
            "group_id" => null,
            "priority" => 1,
            "requester_id" => 123,
            "responder_id" => null,
            "source" => 2,
            "status" => 2,
            "subject" => "Support needed..",
            "company_id" => 1,
            "id" => 1,
            "type" => "Question",
            "to_emails" => null,
            "product_id" => null,
            "fr_escalated" => false,
            "spam" => false,
            "urgent" => false,
            "is_escalated" => false,
            "created_at" => "2020-10-20T13:08:06Z",
            "updated_at" => "2020-10-20T13:08:06Z",
            "due_by" => "2020-10-25T13:08:06Z",
            "fr_due_by" => "2020-10-25T13:08:06Z",
            "description_text" => "Some details on the issue ...",
            "description" => "<div>Some details on the issue ..</div>",
            "tags" => [],
            "attachments" => []
        ];

        return $response;
    }

    public function getCustomerTickets($queryString, string $urlKey = 'urlind')
    {
        $successResponse = [
            [
                'priority' => 1,
                'requester_id' => 42020620300,
                'source' => 2,
                'company_id' => null,
                'status' => 5,
                'subject' => '',
                'id' => 3358,
                'type' => null,
                'due_by' => '2020-11-02T11:02:50Z',
                'fr_due_by' => '2020-10-29T11:02:50Z',
                'is_escalated' => false,
                'custom_fields' => [
                    'cf_category' => null,
                    'cf_merchant_id' => 'CCOhinUeUsT8HN',
                    'cf_source' => null,
                    'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method' => null,
                    'cf_product' => null,
                    'cf_escalation_reason' => null,
                    'cf_platform' => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id' => null,
                    'cf_order_id' => null,
                    'cf_payment_email' => 'moulikak@razorpay.com',
                    'cf_payment_phone' => ''
                ],
                'created_at' => '2020-10-28T11:02:50Z',
                'updated_at' => '2020-10-28T11:02:51Z'
            ],
            [
                'priority' => 1,
                'requester_id' => 42020620300,
                'source' => 2,
                'company_id' => null,
                'status' => 5,
                'subject' => '',
                'id' => 3328,
                'type' => null,
                'due_by' => '2020-11-02T11 =>02 =>50Z',
                'fr_due_by' => '2020-10-29T11 =>02 =>50Z',
                'is_escalated' => false,
                'custom_fields' => [
                    'cf_category' => null,
                    'cf_merchant_id' => 'CCOhinUeUsT8HN',
                    'cf_source' => null,
                    'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method' => null,
                    'cf_product' => null,
                    'cf_escalation_reason' => null,
                    'cf_platform' => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id' => null,
                    'cf_order_id' => null,
                    'cf_payment_email' => 'moulikak@razorpay.com',
                    'cf_payment_phone' => ''
                ],
                'created_at' => '2020-10-28T11:02:50Z',
                'updated_at' => '2020-10-28T11:02:51Z'
            ]
        ];

        $successResponseSecond = [
            [
                'priority' => 1,
                'requester_id' => 42020620300,
                'source' => 2,
                'company_id' => null,
                'status' => 5,
                'subject' => '',
                'id' => 3368,
                'type' => 'Other',
                'due_by' => '2020-11-02T11:02:50Z',
                'fr_due_by' => '2020-10-29T11:02:50Z',
                'is_escalated' => false,
                'custom_fields' => [
                    'cf_category' => null,
                    'cf_merchant_id' => 'CCOhinUeUsT8HN',
                    'cf_source' => null,
                    'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method' => null,
                    'cf_product' => null,
                    'cf_escalation_reason' => null,
                    'cf_platform' => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id' => null,
                    'cf_order_id' => null,
                    'cf_payment_email' => 'moulikak@razorpay.com',
                    'cf_payment_phone' => ''
                ],
                'created_at' => '2020-10-28T11:02:50Z',
                'updated_at' => '2020-10-28T11:02:51Z'
            ],
            [
                'priority' => 1,
                'requester_id' => 42020620300,
                'source' => 2,
                'company_id' => null,
                'status' => 5,
                'subject' => '',
                'id' => 3338,
                'type' => null,
                'due_by' => '2020-11-02T11 =>02 =>50Z',
                'fr_due_by' => '2020-10-29T11 =>02 =>50Z',
                'is_escalated' => false,
                'custom_fields' => [
                    'cf_category'            => null,
                    'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                    'cf_source'              => null,
                    'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method'      => null,
                    'cf_product'             => null,
                    'cf_escalation_reason'   => null,
                    'cf_platform'            => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id'           => null,
                    'cf_order_id'            => null,
                    'cf_payment_email'       => 'moulikak@razorpay.com',
                    'cf_payment_phone'       => ''
                ],
                'created_at' => '2020-10-28T11:02:50Z',
                'updated_at' => '2020-10-28T11:02:51Z'
            ],
            [
                'priority'      => 1,
                'requester_id'  => 42020620300,
                'source'        => 2,
                'company_id'    => null,
                'status'        => 11,
                'subject'       => '',
                'id'            => 3339,
                'type'          => null,
                'due_by'        => '2020-11-02T11 =>02 =>50Z',
                'fr_due_by'     => '2020-10-29T11 =>02 =>50Z',
                'is_escalated'  => false,
                'custom_fields' => [
                    'cf_category'            => null,
                    'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                    'cf_source'              => null,
                    'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method'      => null,
                    'cf_product'             => null,
                    'cf_escalation_reason'   => null,
                    'cf_platform'            => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id'           => null,
                    'cf_order_id'            => null,
                    'cf_payment_email'       => 'moulikak@razorpay.com',
                    'cf_payment_phone'       => ''
                ],
                'created_at'    => '2020-10-28T11:02:50Z',
                'updated_at'    => '2020-10-28T11:02:51Z'
            ],
        ];

        $successResponseSecondNodal = [
            [
                'cc_emails'                => [],
                'fwd_emails'               => [],
                'reply_cc_emails'          => [],
                'ticket_cc_emails'         => [],
                'fr_escalated'             => false,
                'spam'                     => false,
                'email_config_id'          => null,
                'group_id'                 => 11000003635,
                'priority'                 => 1,
                'requester_id'             => 11033774580,
                'responder_id'             => null,
                'source'                   => 2,
                'company_id'               => null,
                'status'                   => 2,
                'subject'                  => "",
                'association_type'         => null,
                'to_emails'                => null,
                'product_id'               => null,
                'id'                       => 9993,
                'tags'                     => ['tag1'],
                'type'                     => 'Other',
                'due_by'                   => "2020-10-13T06:05:31Z",
                'fr_due_by'                => "2020-10-09T06:05:31Z",
                'is_escalated'             => false,
                'description'              => "<div>test<br><b>Contact Number: </b>08095853344<br>\n</div>",
                'description_text'         => "test Contact Number: 08095853344",
                'custom_fields'            => [
                    'cf_category'            => null,
                    'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                    'cf_source'              => null,
                    'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method'      => null,
                    'cf_product'             => null,
                    'cf_escalation_reason'   => null,
                    'cf_platform'            => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id'           => null,
                    'cf_order_id'            => null,
                    'cf_payment_email'       => 'moulikak@razorpay.com',
                    'cf_payment_phone'       => ''
                ],
                'created_at'               => "2022-02-12T11:05:31Z",
                'updated_at'               => "2022-02-12T11:18:25Z",
                'associated_tickets_count' => null,
                'nr_due_by'                => null,
                'nr_escalated'             => false
            ],
            [
                'cc_emails'                => [],
                'fwd_emails'               => [],
                'reply_cc_emails'          => [],
                'ticket_cc_emails'         => [],
                'fr_escalated'             => false,
                'tags'                     => ['assistant_nodal'],
                'spam'                     => false,
                'email_config_id'          => null,
                'group_id'                 => 11000003635,
                'priority'                 => 1,
                'requester_id'             => 11033774580,
                'responder_id'             => null,
                'source'                   => 2,
                'company_id'               => null,
                'status'                   => 5,
                'subject'                  => "",
                'association_type'         => null,
                'to_emails'                => null,
                'product_id'               => null,
                'id'                       => 9994,
                'type'                     => 'Other',
                'due_by'                   => "2020-10-13T06:05:31Z",
                'fr_due_by'                => "2020-10-09T06:05:31Z",
                'is_escalated'             => false,
                'description'              => "<div>test<br><b>Contact Number: </b>08095853344<br>\n</div>",
                'description_text'         => "test Contact Number: 08095853344",
                'custom_fields'            => [
                    'cf_category'            => null,
                    'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                    'cf_source'              => null,
                    'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method'      => null,
                    'cf_product'             => null,
                    'cf_escalation_reason'   => null,
                    'cf_platform'            => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id'           => null,
                    'cf_order_id'            => null,
                    'cf_payment_email'       => 'moulikak@razorpay.com',
                    'cf_payment_phone'       => ''
                ],
                'created_at'               => "2022-02-12T11:05:31Z",
                'updated_at'               => "2022-02-12T11:18:25Z",
                'associated_tickets_count' => null,
                'nr_due_by'                => null,
                'nr_escalated'             => false
            ],
            [
                'cc_emails'                => [],
                'fwd_emails'               => [],
                'reply_cc_emails'          => [],
                'tags'                     => [],
                'ticket_cc_emails'         => [],
                'fr_escalated'             => false,
                'spam'                     => false,
                'email_config_id'          => null,
                'group_id'                 => 11000003635,
                'priority'                 => 1,
                'requester_id'             => 11033774580,
                'responder_id'             => null,
                'source'                   => 2,
                'company_id'               => null,
                'status'                   => 6,
                'subject'                  => "",
                'association_type'         => null,
                'to_emails'                => null,
                'product_id'               => null,
                'id'                       => 9995,
                'type'                     => 'Other',
                'due_by'                   => "2020-10-13T06:05:31Z",
                'fr_due_by'                => "2020-10-09T06:05:31Z",
                'is_escalated'             => false,
                'description'              => "<div>test<br><b>Contact Number: </b>08095853344<br>\n</div>",
                'description_text'         => "test Contact Number: 08095853344",
                'custom_fields'            => [
                    'cf_category'            => null,
                    'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                    'cf_source'              => null,
                    'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method'      => null,
                    'cf_product'             => null,
                    'cf_escalation_reason'   => null,
                    'cf_platform'            => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id'           => null,
                    'cf_order_id'            => null,
                    'cf_payment_email'       => 'moulikak@razorpay.com',
                    'cf_payment_phone'       => ''
                ],
                'created_at'               => "2022-02-02T11:05:31Z",
                'updated_at'               => "2022-02-02T11:18:25Z",
                'associated_tickets_count' => null,
                'nr_due_by'                => null,
                'nr_escalated'             => false
            ],
            [
                'cc_emails'                => [],
                'fwd_emails'               => [],
                'reply_cc_emails'          => [],
                'ticket_cc_emails'         => [],
                'fr_escalated'             => false,
                'spam'                     => false,
                'email_config_id'          => null,
                'group_id'                 => 11000003635,
                'priority'                 => 1,
                'requester_id'             => 11033774580,
                'responder_id'             => null,
                'source'                   => 2,
                'company_id'               => null,
                'status'                   => 3,
                'subject'                  => "",
                'association_type'         => null,
                'to_emails'                => null,
                'product_id'               => null,
                'id'                       => 9996,
                'type'                     => 'Other',
                'due_by'                   => "2020-10-13T06:05:31Z",
                'fr_due_by'                => "2020-10-09T06:05:31Z",
                'is_escalated'             => false,
                'description'              => "<div>test<br><b>Contact Number: </b>08095853344<br>\n</div>",
                'description_text'         => "test Contact Number: 08095853344",
                'custom_fields'            => [
                    'cf_category'            => null,
                    'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                    'cf_source'              => null,
                    'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                    'cf_payment_method'      => null,
                    'cf_product'             => null,
                    'cf_escalation_reason'   => null,
                    'cf_platform'            => null,
                    'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                    'cf_refund_id'           => null,
                    'cf_order_id'            => null,
                    'cf_payment_email'       => 'moulikak@razorpay.com',
                    'cf_payment_phone'       => ''
                ],
                'created_at'               => "2022-02-02T11:05:31Z",
                'updated_at'               => "2022-02-02T11:18:25Z",
                'associated_tickets_count' => null,
                'tags'                     => ['assistant_nodal'],
                'nr_due_by'                => null,
                'nr_escalated'             => false
            ],
        ];

        $failureResponse = [
            'description' => "Validation failed",
            'errors'      => [
                'field'   => 'email',
                'message' => 'There is no contact matching the given email',
                'code'    => 'invalid_value'
            ]
        ];

        switch ($queryString)
        {
            case 'email=success%40gmail.com':
                if ($urlKey === 'urlind')
                {
                    return $successResponseSecond;
                }

                return $successResponse;
            case 'email=successnodal%40gmail.com':
                return $successResponseSecondNodal;
            case 'email=failure%40gmail.com':
                return $failureResponse;
            default:
                return $successResponse;
        }
    }

    public function fetchTicketById(string $ticketId, $urlKey = 'urlind')
    {
        switch ($ticketId)
        {
            case 3328:
            case 3329:
                return [
                    'priority' => 1,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 3,
                    'subject' => '',
                    'id' => 3328,
                    'type' => null,
                    'tags' => [],
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'requester' => [
                        'id' => 42020620300,
                        'name' => 'Some name',
                        'email' => 'thatemail@razorpay.com',
                        'mobile' => null,
                        'phone' => null
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            case 3331:
                if ($urlKey !== 'urlind') {
                    return [];
                }
                return [
                    'priority' => 1,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 3,
                    'tags' => [],
                    'subject' => '',
                    'id' => 3331,
                    'type' => null,
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'requester' => [
                        'id' => 42020620300,
                        'name' => 'Some name',
                        'email' => 'thatemail@razorpay.com',
                        'mobile' => null,
                        'phone' => null
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            case 9991:
                return [
                    'priority' => 4,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 2,
                    'subject' => '',
                    'id' => 9991,
                    'description' => 'some description',
                    'group_id' => 14000000008345,
                    'type' => null,
                    'tags' => ['assistant_nodal','tag1'],
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'requester' => [
                        'id' => 42020620300,
                        'name' => 'Some name',
                        'email' => 'thatemail@razorpay.com',
                        'mobile' => null,
                        'phone' => null
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            case 9992:
                return [
                    'priority' => 4,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 2,
                    'subject' => '',
                    'id' => 9992,
                    'description' => 'some description',
                    'group_id' => 14000000008346,
                    'tags' => ['nodal'],
                    'type' => null,
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'requester' => [
                        'id' => 42020620300,
                        'name' => 'Some name',
                        'email' => 'thatemail@razorpay.com',
                        'mobile' => null,
                        'phone' => null
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            default:
                return [
                    'tags' => [],
                    'priority' => 1,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 3,
                    'subject' => '',
                    'id' => 3330,
                    'type' => null,
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'requester' => [
                        'id' => 42020620300,
                        'name' => 'Some other name',
                        'email' => 'someotheremail@razorpay.com',
                        'mobile' => null,
                        'phone' => null
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
        }
    }

    public function updateTicketV2(string $ticketId, array $input, $urlKey = 'urlind')
    {
        switch ($ticketId)
        {
            case 3328:
                return [
                    'priority'      => 4,
                    'requester_id'  => 42020620300,
                    'source'        => 2,
                    'company_id'    => null,
                    'status'        => 2,
                    'tags'          => [],
                    'subject'       => '',
                    'id'            => 3328,
                    'description'   => 'some description',
                    'type'          => null,
                    'due_by'        => '2020-11-02T11:02:50Z',
                    'fr_due_by'     => '2020-10-29T11:02:50Z',
                    'is_escalated'  => false,
                    'custom_fields' => [
                        'cf_category'            => null,
                        'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            case 3331:
                return [
                    'priority'      => 4,
                    'requester_id'  => 42020620300,
                    'source'        => 2,
                    'company_id'    => null,
                    'status'        => 2,
                    'subject'       => '',
                    'id'            => 3331,
                    'tags'          => [],
                    'description'   => 'some description',
                    'type'          => null,
                    'due_by'        => '2020-11-02T11:02:50Z',
                    'fr_due_by'     => '2020-10-29T11:02:50Z',
                    'is_escalated'  => false,
                    'custom_fields' => [
                        'cf_category'            => null,
                        'cf_merchant_id'         => 'CCOhinUeUsT8HN',
                        'cf_source'              => null,
                        'cf_transaction_id'      => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            case 9991:
                return [
                    'priority' => 4,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 2,
                    'subject' => '',
                    'id' => 9991,
                    'description' => 'some description',
                    'group_id' => 14000000008345,
                    'type' => null,
                    'tags' => ['assistant_nodal','tag1'],
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            case 9992:
                return [
                    'priority' => 4,
                    'requester_id' => 42020620300,
                    'source' => 2,
                    'company_id' => null,
                    'status' => 2,
                    'subject' => '',
                    'id' => 9992,
                    'description' => 'some description',
                    'group_id' => 14000000008346,
                    'tags' => ['nodal'],
                    'type' => null,
                    'due_by' => '2020-11-02T11:02:50Z',
                    'fr_due_by' => '2020-10-29T11:02:50Z',
                    'is_escalated' => false,
                    'custom_fields' => [
                        'cf_category' => null,
                        'cf_merchant_id' => 'CCOhinUeUsT8HN',
                        'cf_source' => null,
                        'cf_transaction_id' => 'pay_FrTYsVAuCrW8Fm',
                        'cf_payment_method' => null,
                        'cf_product' => null,
                        'cf_escalation_reason' => null,
                        'cf_platform' => null,
                        'cf_razorpay_payment_id' => 'FrTYsVAuCrW8Fm',
                        'cf_refund_id' => null,
                        'cf_order_id' => null,
                        'cf_payment_email' => 'moulikak@razorpay.com',
                        'cf_payment_phone' => ''
                    ],
                    'created_at' => '2020-10-28T11:02:50Z',
                    'updated_at' => '2020-10-28T11:02:51Z'
                ];
            default:
                return [];
        }
    }

    public function addNoteToTicket(string $ticketId, array $input, $urlKey = 'urlind')
    {
        return [
            'body' => '<div>some description</div>',
            'body_text' => 'some description',
            'id' => 42068693079,
            'incoming' => false,
            'private' => false,
            'user_id' => 42005730186,
            'support_email' => null,
            'ticket_id' => 3731,
            'to_emails' => [],
            'created_at' => '2020-11-11T07 =>00 =>35Z',
            'updated_at' => '2020-11-11T07 =>00 =>35Z',
            'attachments' => []
        ];
    }
}
