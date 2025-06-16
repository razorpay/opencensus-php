<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Rzp\Userservice\V1\Invitation;

/**
 * InvitationService is the gRPC service for invitation entity in user-service
 */
class InvitationServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * CreateInvitation is the rpc call to create invitation for user
     * @param \Rzp\Userservice\V1\Invitation\CreateInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateInvitation(\Rzp\Userservice\V1\Invitation\CreateInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/CreateInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * UpdateInvitation is the rpc call to update invitation for user
     * @param \Rzp\Userservice\V1\Invitation\UpdateInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateInvitation(\Rzp\Userservice\V1\Invitation\UpdateInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/UpdateInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * GetInvitation is the rpc call to get invitation by id or token
     * @param \Rzp\Userservice\V1\Invitation\GetInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetInvitation(\Rzp\Userservice\V1\Invitation\GetInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/GetInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * GetInvitationsForAccount is the rpc call to get invitations for an account
     * @param \Rzp\Userservice\V1\Invitation\GetInvitationsForAccountRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetInvitationsForAccount(\Rzp\Userservice\V1\Invitation\GetInvitationsForAccountRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/GetInvitationsForAccount',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\GetInvitationsForAccountResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ResendInvitation is the rpc call to resend invitation
     * @param \Rzp\Userservice\V1\Invitation\ResendInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResendInvitation(\Rzp\Userservice\V1\Invitation\ResendInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/ResendInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * DeleteInvitation is the rpc call to delete invitation
     * @param \Rzp\Userservice\V1\Invitation\DeleteInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteInvitation(\Rzp\Userservice\V1\Invitation\DeleteInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/DeleteInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Rzp\Userservice\V1\Invitation\InvitationActionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function InvitationAction(\Rzp\Userservice\V1\Invitation\InvitationActionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/InvitationAction',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Rzp\Userservice\V1\Invitation\DraftInvitationAcceptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DraftInvitationAccept(\Rzp\Userservice\V1\Invitation\DraftInvitationAcceptRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/DraftInvitationAccept',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\DraftInvitationAcceptResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Rzp\Userservice\V1\Invitation\CreateVendorPortalInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateVendorPortalInvitation(\Rzp\Userservice\V1\Invitation\CreateVendorPortalInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/CreateVendorPortalInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Rzp\Userservice\V1\Invitation\ResendVendorPortalInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResendVendorPortalInvitation(\Rzp\Userservice\V1\Invitation\ResendVendorPortalInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/ResendVendorPortalInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Rzp\Userservice\V1\Invitation\FetchDraftInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function FetchDraftInvitation(\Rzp\Userservice\V1\Invitation\FetchDraftInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/FetchDraftInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\FetchDraftInvitationResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Rzp\Userservice\V1\Invitation\CreateInvitationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateXperienceUserInvitation(\Rzp\Userservice\V1\Invitation\CreateInvitationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/rzp.userservice.v1.invitation.InvitationService/CreateXperienceUserInvitation',
        $argument,
        ['\Rzp\Userservice\V1\Invitation\Invitation', 'decode'],
        $metadata, $options);
    }

}
