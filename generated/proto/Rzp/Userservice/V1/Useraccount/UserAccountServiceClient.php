<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Rzp\Userservice\V1\Useraccount;

/**
 * UserAccountService is the gRPC service for user account entity
 */
class UserAccountServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * CreateAccountForUser is the rpc call to create account for user
     * @param \Rzp\Userservice\V1\Useraccount\CreateAccountForUserRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateAccountForUser(\Rzp\Userservice\V1\Useraccount\CreateAccountForUserRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/CreateAccountForUser',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\UserAccount', 'decode'],
        $metadata, $options);
    }

    /**
     * CreateUserAccounts is the rpc call to create user account mapping
     * @param \Rzp\Userservice\V1\Useraccount\CreateUserAccountsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateUserAccounts(\Rzp\Userservice\V1\Useraccount\CreateUserAccountsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/CreateUserAccounts',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\CreateUserAccountsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UpdateUserAccounts is the rpc call to update user account
     * @param \Rzp\Userservice\V1\Useraccount\UpdateUserAccountsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateUserAccounts(\Rzp\Userservice\V1\Useraccount\UpdateUserAccountsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/UpdateUserAccounts',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\UpdateUserAccountsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DeleteUserAccount is the rpc call to delete user account
     * @param \Rzp\Userservice\V1\Useraccount\DeleteUserAccountsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteUserAccounts(\Rzp\Userservice\V1\Useraccount\DeleteUserAccountsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/DeleteUserAccounts',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\DeleteUserAccountsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetUserAccountsByAttributes is the rpc call to get user accounts by attributes like
     * user_id, account_id, product, role, appsflyer_id
     * @param \Rzp\Userservice\V1\Useraccount\GetUserAccountsByAttributesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetUserAccountsByAttributes(\Rzp\Userservice\V1\Useraccount\GetUserAccountsByAttributesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/GetUserAccountsByAttributes',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\GetUserAccountsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetUserAccountsByUsers is the rpc call to get user accounts by user_ids
     * @param \Rzp\Userservice\V1\Useraccount\GetUserAccountsByUserIDsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetUserAccountsByUsers(\Rzp\Userservice\V1\Useraccount\GetUserAccountsByUserIDsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/GetUserAccountsByUsers',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\GetUserAccountsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetUserAccountsByAccounts is the rpc call to get user accounts by account_ids
     * @param \Rzp\Userservice\V1\Useraccount\GetUserAccountsByAccountIDsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetUserAccountsByAccounts(\Rzp\Userservice\V1\Useraccount\GetUserAccountsByAccountIDsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.useraccount.UserAccountService/GetUserAccountsByAccounts',
        $argument,
        ['\Rzp\Userservice\V1\Useraccount\GetUserAccountsResponse', 'decode'],
        $metadata, $options);
    }

}
