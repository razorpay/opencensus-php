import { Component } from 'react';
import { connect } from 'react-redux';

import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import {
    createTransferBatch,
    validateTransferBatch,
    createLinkedAccountBatch,
    validateLinkedAccountBatch,
} from 'merchant/reducers/batches';
import { closeModal } from 'merchant_common/reducers/modals';


const gaEvents = setGaTrack('Dashboard - Route - BU');

@connect((state) => ({ user: state.session.user }), {
    createTransferBatch,
    validateTransferBatch,
    createLinkedAccountBatch,
    validateLinkedAccountBatch,
    closeModal,
})
export default class CreateHostedMandateBatch extends Component {
    renderTransfersModal = () => (
        <BatchUpload
            acceptFileInfo={['csv', 'xlsx']} 
            createBatch={this.props.createTransferBatch}
            validateBatch={this.props.validateTransferBatch}
            gaEvents={gaEvents}
            maxRows="50,000" 
            maxFileSize={11534336} // 11 MB
            batchType="payment_transfer"
            docUrl="https://razorpay.com/docs/route/dashboard/batch-upload/" 
            sampleUrl="/files/sample_batch_payment_transfer.xlsx" 
            processingOptions={true}
        />
    );

    renderLinkedAccountsModal = () => (
        <BatchUpload
            acceptFileInfo={['csv', 'xlsx']} 
            createBatch={this.props.createLinkedAccountBatch}
            validateBatch={this.props.validateLinkedAccountBatch}
            gaEvents={gaEvents}
            maxRows="50,000" 
            maxFileSize={11534336} // 11 MB
            batchType="linked_account_create"
            docUrl="https://razorpay.com/docs/route/dashboard/batch-upload/" 
            sampleUrl="/files/sample_batch_linked_account.xlsx" 
            processingOptions={true}
        />
    );

    render() {
        const { openUploadModal, user } = this.props;

        return (
            <div class="RouteBatch--dropdown">
                <div
                    class="panel panel-default"
                    onClick={openUploadModal(this.renderTransfersModal)}
                >
                    <div class="panel-body">
                        <img src="/dist/css/assets/marketplace/transfers.svg" />
                        <div class="description">
                            <div class="text-primary">
                                <strong>Transfers</strong>
                            </div>
                            <div>Create transfers in batch</div>
                        </div>
                        <i class="i-chevron-right pull-right text-primary" />
                    </div>
                </div>
                <div
                    class="panel panel-default"
                    onClick={openUploadModal(this.renderLinkedAccountsModal)}
                >
                    <div class="panel-body">
                        <img src="/dist/css/assets/marketplace/linked_accounts.svg" />
                        <div class="description">
                            <div class="text-primary">
                                <strong>Linked accounts</strong>
                            </div>
                            <div>Create linked accounts in a batch</div>
                        </div>
                        <i class="i-chevron-right pull-right text-primary" />
                    </div>
                </div>
            </div>
        );
    }
}
