<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Rzp\Userservice\V1\User;

/**
 * UserService is the gRPC service for user entity in user-service
 */
class UserServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * CreateUser is the rpc call to create user
     * @param \Rzp\Userservice\V1\User\CreateUserRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateUser(\Rzp\Userservice\V1\User\CreateUserRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.user.UserService/CreateUser',
        $argument,
        ['\Rzp\Userservice\V1\User\User', 'decode'],
        $metadata, $options);
    }

    /**
     * UpdateUser is the rpc call to update user
     * @param \Rzp\Userservice\V1\User\UpdateUserRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateUser(\Rzp\Userservice\V1\User\UpdateUserRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.user.UserService/UpdateUser',
        $argument,
        ['\Rzp\Userservice\V1\User\User', 'decode'],
        $metadata, $options);
    }

    /**
     * GetUser is the rpc call to get user by id
     * @param \Rzp\Userservice\V1\User\GetUserRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetUser(\Rzp\Userservice\V1\User\GetUserRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.user.UserService/GetUser',
        $argument,
        ['\Rzp\Userservice\V1\User\User', 'decode'],
        $metadata, $options);
    }

    /**
     * GetUsersByAttributes is the rpc call to get users by attributes
     * @param \Rzp\Userservice\V1\User\GetUsersByAttributesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetUsersByAttributes(\Rzp\Userservice\V1\User\GetUsersByAttributesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.user.UserService/GetUsersByAttributes',
        $argument,
        ['\Rzp\Userservice\V1\User\GetUsersByAttributesResponse', 'decode'],
        $metadata, $options);
    }

}
