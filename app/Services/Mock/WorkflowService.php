<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response;

class WorkflowService extends \RZP\Services\WorkflowService
{
    public function request(string $path, array $payload)
    {
        return $this->mockedResponse($path, $payload);
    }

    private function mockedResponse(string $path, array $payload)
    {
        $this->trace->histogram(
            self::WORKFLOW_SERVICE_REQUEST_MILLISECONDS,
            100);

        // Just for tests!
        $pathArr = explode('/', $path);

        if ($pathArr[count($pathArr) - 1] == 'CreateDirectOnWorkflow')
        {
            return $this->sendWFRejectMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] == 'CreateWithEntityId' && $payload['action'] === 'approved')
        {
            return $this->sendWFApproveMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] == 'CreateWithEntityId' && $payload['action'] === 'rejected')
        {
            return $this->sendWFRejectMockResponse();
        }
        elseif ($pathArr[1] == 'rzp.workflows.workflow.v1.WorkflowAPI' && $pathArr[2] == 'Get')
        {
            return $this->sendWFGetResponse();
        }
        elseif ($pathArr[1] == 'rzp.workflows.workflow.v1.WorkflowAPI' && $pathArr[2] == 'Create')
        {
            return $this->sendWFCreateMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] == 'Create')
        {
            return $this->sendCreateMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] === 'CreateV2')
        {
            return $this->sendCreateMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] === 'UpdateV2')
        {
            return $this->sendCreateMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] === 'DeleteV2')
        {
            return $this->sendDeleteMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] == 'Update')
        {
            return $this->sendUpdateMockResponse();
        }
        elseif ($pathArr[count($pathArr) - 1] == 'Get')
        {
            return $this->sendUpdateMockResponse();
        }

        return new \WpOrg\Requests\Response;
    }

    private function sendCreateMockResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->body = '{
                                "id": "FQE6Xw4ZpoM21X",
                                "name": "10000000000000 - Payout approval workflow",
                                "template": {
                                    "state_transitions": {
                                        "0_1k_workflow": {
                                            "current_state": "0_1k_workflow",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "10k_10Cr_workflow": {
                                            "current_state": "10k_10Cr_workflow",
                                            "next_states": [
                                                "Owner_Approval"
                                            ]
                                        },
                                        "1k_10k_workflow": {
                                            "current_state": "1k_10k_workflow",
                                            "next_states": [
                                                "FL1_Approval",
                                                "FL4_Approval"
                                            ]
                                        },
                                        "Admin_Approval": {
                                            "current_state": "Admin_Approval",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "And_1_result": {
                                            "current_state": "And_1_result",
                                            "next_states": [
                                                "FL2_Approval",
                                                "Admin_Approval"
                                            ]
                                        },
                                        "FL1_Approval": {
                                            "current_state": "FL1_Approval",
                                            "next_states": [
                                                "And_1_result"
                                            ]
                                        },
                                        "FL2_Approval": {
                                            "current_state": "FL2_Approval",
                                            "next_states": [
                                                "FL3_Approval"
                                            ]
                                        },
                                        "FL3_Approval": {
                                            "current_state": "FL3_Approval",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "FL4_Approval": {
                                            "current_state": "FL4_Approval",
                                            "next_states": [
                                                "And_1_result"
                                            ]
                                        },
                                        "Owner_Approval": {
                                            "current_state": "Owner_Approval",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "START_STATE": {
                                            "current_state": "START_STATE",
                                            "next_states": [
                                                "0_1k_workflow",
                                                "1k_10k_workflow",
                                                "10k_10Cr_workflow"
                                            ]
                                        }
                                    },
                                    "states_data": {
                                        "0_1k_workflow": {
                                            "name": "0_1k_workflow",
                                            "group": "ABC",
                                            "type": "between",
                                            "rules": {
                                                "min": "1",
                                                "max": "1000",
                                                "key": "amount"
                                            }
                                        },
                                        "10k_10Cr_workflow": {
                                            "name": "10k_10Cr_workflow",
                                            "group": "ABC",
                                            "type": "between",
                                            "rules": {
                                                "min": "10000",
                                                "max": "100000000",
                                                "key": "amount"
                                            }
                                        },
                                        "1k_10k_workflow": {
                                            "name": "1k_10k_workflow",
                                            "group": "ABC",
                                            "type": "between",
                                            "rules": {
                                                "min": "1000",
                                                "max": "10000",
                                                "key": "amount"
                                            }
                                        },
                                        "Admin_Approval": {
                                            "name": "Admin_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "admin",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "And_1_result": {
                                            "name": "And_1_result",
                                            "group": "ABC",
                                            "type": "merge_states",
                                            "rules": {
                                                "states": [
                                                    "FL1_Approval",
                                                    "FL4_Approval"
                                                ]
                                            }
                                        },
                                        "FL1_Approval": {
                                            "name": "FL1_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl1",
                                                "count": 2
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "FL2_Approval": {
                                            "name": "FL2_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl2",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "FL3_Approval": {
                                            "name": "FL3_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl3",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "FL4_Approval": {
                                            "name": "FL4_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl4",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "Owner_Approval": {
                                            "name": "Owner_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "owner",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        }
                                    },
                                    "allowed_actions": [
                                        "approved",
                                        "rejected"
                                    ],
                                    "meta": {
                                        "domain": "payouts",
                                        "task_list_name": "payouts-approval",
                                        "workflow_expire_time": "3000"
                                    },
                                    "type": "approval"
                                },
                                "type": "payout-approval",
                                "version": 1,
                                "owner_id": "10000000000000",
                                "owner_type": "merchant",
                                "context": {
                                    "aa": "test context"
                                },
                                "enabled": "true",
                                "service": "rx_live",
                                "org_id": "100000razorpay",
                                "created_at": "1597317215"
                            }';

        $response->status_code = 200;

        return $response;
    }

    private function sendDeleteMockResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->body = '{}';

        $response->status_code = 200;

        return $response;
    }

    private function sendUpdateMockResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->body = '{
                                "id": "FQE6Xw4ZpoM21X",
                                "name": "10000000000000 - Payout approval workflow",
                                "template": {
                                    "state_transitions": {
                                        "0_1k_workflow": {
                                            "current_state": "0_1k_workflow",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "10k_10Cr_workflow": {
                                            "current_state": "10k_10Cr_workflow",
                                            "next_states": [
                                                "Owner_Approval"
                                            ]
                                        },
                                        "1k_10k_workflow": {
                                            "current_state": "1k_10k_workflow",
                                            "next_states": [
                                                "FL1_Approval",
                                                "FL4_Approval"
                                            ]
                                        },
                                        "Admin_Approval": {
                                            "current_state": "Admin_Approval",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "And_1_result": {
                                            "current_state": "And_1_result",
                                            "next_states": [
                                                "FL2_Approval",
                                                "Admin_Approval"
                                            ]
                                        },
                                        "FL1_Approval": {
                                            "current_state": "FL1_Approval",
                                            "next_states": [
                                                "And_1_result"
                                            ]
                                        },
                                        "FL2_Approval": {
                                            "current_state": "FL2_Approval",
                                            "next_states": [
                                                "FL3_Approval"
                                            ]
                                        },
                                        "FL3_Approval": {
                                            "current_state": "FL3_Approval",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "FL4_Approval": {
                                            "current_state": "FL4_Approval",
                                            "next_states": [
                                                "And_1_result"
                                            ]
                                        },
                                        "Owner_Approval": {
                                            "current_state": "Owner_Approval",
                                            "next_states": [
                                                "END_STATE"
                                            ]
                                        },
                                        "START_STATE": {
                                            "current_state": "START_STATE",
                                            "next_states": [
                                                "0_1k_workflow",
                                                "1k_10k_workflow",
                                                "10k_10Cr_workflow"
                                            ]
                                        }
                                    },
                                    "states_data": {
                                        "0_1k_workflow": {
                                            "name": "0_1k_workflow",
                                            "group": "ABC",
                                            "type": "between",
                                            "rules": {
                                                "min": "1",
                                                "max": "1000",
                                                "key": "amount"
                                            }
                                        },
                                        "10k_10Cr_workflow": {
                                            "name": "10k_10Cr_workflow",
                                            "group": "ABC",
                                            "type": "between",
                                            "rules": {
                                                "min": "10000",
                                                "max": "100000000",
                                                "key": "amount"
                                            }
                                        },
                                        "1k_10k_workflow": {
                                            "name": "1k_10k_workflow",
                                            "group": "ABC",
                                            "type": "between",
                                            "rules": {
                                                "min": "1000",
                                                "max": "10000",
                                                "key": "amount"
                                            }
                                        },
                                        "Admin_Approval": {
                                            "name": "Admin_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "admin",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "And_1_result": {
                                            "name": "And_1_result",
                                            "group": "ABC",
                                            "type": "merge_states",
                                            "rules": {
                                                "states": [
                                                    "FL1_Approval",
                                                    "FL4_Approval"
                                                ]
                                            }
                                        },
                                        "FL1_Approval": {
                                            "name": "FL1_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl1",
                                                "count": 2
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "FL2_Approval": {
                                            "name": "FL2_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl2",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "FL3_Approval": {
                                            "name": "FL3_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl3",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "FL4_Approval": {
                                            "name": "FL4_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "fl4",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        },
                                        "Owner_Approval": {
                                            "name": "Owner_Approval",
                                            "group": "ABC",
                                            "type": "checker",
                                            "rules": {
                                                "actor_property_key": "role",
                                                "actor_property_value": "owner",
                                                "count": 1
                                            },
                                            "callbacks": {
                                                "status": {
                                                    "in": [
                                                        "created",
                                                        "processed"
                                                    ]
                                                }
                                            }
                                        }
                                    },
                                    "allowed_actions": [
                                        "approved",
                                        "rejected"
                                    ],
                                    "meta": {
                                        "domain": "payouts",
                                        "task_list_name": "payouts-approval",
                                        "workflow_expire_time": "3000"
                                    },
                                    "type": "approval"
                                },
                                "type": "payout-approval",
                                "version": 1,
                                "owner_id": "10000000000000",
                                "owner_type": "merchant",
                                "context": {
                                    "aa": "test context"
                                },
                                "enabled": "false",
                                "service": "rx_live",
                                "org_id": "100000razorpay",
                                "created_at": "1597317215"
                            }';

        $response->status_code = 200;

        return $response;
    }

    private function sendWFApproveMockResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->body = '{
                              "count": 1,
                              "entity": "collection",
                              "items": [
                                {
                                  "id": "FV0rayoQ8epeX6",
                                  "workflow_id": "FV0pSI6zc8v6X2",
                                  "state_id": "FV0pTiztDETNyl",
                                  "action_type": "approved",
                                  "comment": "Approving",
                                  "actor_id": "FV0pAuYEKG1QS9",
                                  "actor_type": "user",
                                  "status": "created",
                                  "actor_property_key": "role",
                                  "actor_property_value": "owner",
                                  "actor_meta": {
                                    "email": "raegan.swaniawski@corkery.com"
                                  },
                                  "created_at": "1598362285"
                                }
                              ]
                            }';

        $response->status_code = 200;

        return $response;

    }

    private function sendWFRejectMockResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->body = '{
                              "count": 1,
                              "entity": "collection",
                              "items": [
                                {
                                  "id": "FV0rayoQ8epeX6",
                                  "workflow_id": "FV0pSI6zc8v6X2",
                                  "state_id": "FV0pTiztDETNyl",
                                  "action_type": "reject",
                                  "comment": "Rejecting",
                                  "actor_id": "FV0pAuYEKG1QS9",
                                  "actor_type": "user",
                                  "status": "created",
                                  "actor_property_key": "role",
                                  "actor_property_value": "owner",
                                  "actor_meta": {
                                    "email": "raegan.swaniawski@corkery.com"
                                  },
                                  "created_at": "1598362285"
                                }
                              ]
                            }';

        $response->status_code = 200;

        return $response;

    }

    private function sendWFGetResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->status_code = 200;

        $content =  '{
                        "id": "FV58BuqLuCP4Cw",
                        "config_id": "FV0aQGxYU4kk4c",
                        "entity_id": "FV57s8rpBqOD6w",
                        "entity_type": "payouts",
                        "title": "title",
                        "description": "[]",
                        "config_version": "1",
                        "creator_id": "10000000000000",
                        "creator_type": "merchant",
                        "diff": {
                            "old": {
                                "amount": null,
                                "merchant_id": null
                            },
                            "new": {
                                "amount": 10000,
                                "merchant_id": "10000000000000"
                            }
                        },
                        "callback_details": {
                            "state_callbacks": {
                                "created": {
                                    "method": "post",
                                    "payload": {
                                        "queue_if_low_balance": true,
                                        "type": "state_callbacks_created"
                                    },
                                    "headers": {
                                        "x-creator-id": ""
                                    },
                                    "service": "api_live",
                                    "type": "basic",
                                    "url_path": "/payouts_internal/FV57s8rpBqOD6w/approve",
                                    "response_handler": {
                                        "type": "success_status_codes",
                                        "success_status_codes": [
                                            201,
                                            200
                                        ]
                                    }
                                },
                                "processed": {
                                    "method": "post",
                                    "payload": {
                                        "queue_if_low_balance": true,
                                        "type": "state_callbacks_processed"
                                    },
                                    "headers": {
                                        "x-creator-id": ""
                                    },
                                    "service": "api_live",
                                    "type": "basic",
                                    "url_path": "/payouts_internal/FV57s8rpBqOD6w/approve",
                                    "response_handler": {
                                        "type": "success_status_codes",
                                        "success_status_codes": [
                                            201,
                                            200
                                        ]
                                    }
                                }
                            },
                            "workflow_callbacks": {
                                "processed": {
                                    "domain_status": {
                                        "approved": {
                                            "method": "post",
                                            "payload": {
                                                "queue_if_low_balance": true,
                                                "type": "workflow_callbacks_approved"
                                            },
                                            "headers": {
                                                "x-creator-id": ""
                                            },
                                            "service": "api_live",
                                            "type": "basic",
                                            "url_path": "/payouts_internal/FV57s8rpBqOD6w/approve",
                                            "response_handler": {
                                                "type": "success_status_codes",
                                                "success_status_codes": [
                                                    201,
                                                    200
                                                ]
                                            }
                                        },
                                        "rejected": {
                                            "method": "post",
                                            "payload": {
                                                "queue_if_low_balance": true,
                                                "type": "state_callbacks_rejected"
                                            },
                                            "headers": {
                                                "x-creator-id": ""
                                            },
                                            "service": "api_live",
                                            "type": "basic",
                                            "url_path": "/payouts_internal/FV57s8rpBqOD6w/reject",
                                            "response_handler": {
                                                "type": "success_status_codes",
                                                "success_status_codes": [
                                                    201,
                                                    200
                                                ]
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        "status": "initiated",
                        "domain_status": "created",
                        "owner_id": "10000000000000",
                        "owner_type": "merchant",
                        "org_id": "100000razorpay",
                        "states": {
                            "Owner_Approval": {
                                "id": "FV58Cedbz6e0a2",
                                "workflow_id": "FV58BuqLuCP4Cw",
                                "status": "created",
                                "name": "Owner_Approval",
                                "group_name": "ABC",
                                "type": "checker",
                                "rules": {
                                    "actor_property_key": "role",
                                    "actor_property_value": "owner",
                                    "count": 1
                                },
                                "pending_on_user": true,
                                "created_at": "1598377315",
                                "updated_at": "1598377315"
                            }
                        },
                        "type": "payout-approval",
                        "pending_on_user": true
                    }';

        $response->body = $content;

        return $response;
    }

    private function sendWFCreateMockResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->body = '{
                                "id": "FQE6Xw4ZpoM21X",
                                "status": "created",
                                "domain_status": "created",
                                "config_id": "DGbcgfTgBCGDTJ"
                            }';

        $response->status_code = 200;

        return $response;
    }
}
