import React from 'react';
import Form from 'ui/Form';

import { adminFetch } from 'common/fetch';

import {
    closeModal,
    notifyError,
    notifySuccess,
    openModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

export default function GatewayUnprocessedRefundsConfig() {
    function onSubmitFetch(body) {
            let payload = {
                url: `live/config/key?key=GATEWAY_UNPROCESSED_REFUNDS`,
            };

            adminFetch(payload).then(response => {
                if (response) {
                    notifySuccess('Fetching refunds present in gateway unprocessed list is successful');
                    closeModal();
                    openModal(
                    <ModalContent header="API Response" noPadding>
                    <div class="code" style={{ width: '650px' }}>
                    {JSON.stringify(response, null, 4)}}
            </div>
                </ModalContent>
            );
        }
    });
}

return (
    <Form class="full-span edit-gateway-unprocessed-refunds-config-action" onSubmit={onSubmitFetch}>
    <div class="form-actions text-center">
    <button class="btn" type="submit">
    Submit
    </button>
    </div>
    </Form>
);

function onSubmitEdit(body) {
    if (body.refund_ids) {
        let payload = {
            url: `live/config/keys`,
            data: {
                GATEWAY_UNPROCESSED_REFUNDS: splitAndFilter(body.refund_ids, ','),
            },
        };

        adminPut(payload).then(response => {
            if (response) {
                notifySuccess('Refunds have been added in unprocessed list successfully.');
                closeModal();
                openModal(
                <ModalContent header="API Response" noPadding>
                <div class="code" style={{ width: '650px' }}>
                {JSON.stringify(response, null, 4)}}
        </div>
            </ModalContent>
        );
    }
});
} else {
    notifyError('Refund Ids are mandatory.');
}
}

return (
    <Form class="full-span edit-gateway-unprocessed-refunds-config-action" onSubmit={onSubmitEdit}>
    <TextAreaField
label="Refund Ids"
type="text"
name="refund_ids"
required
placeholder="Enter comma separated refund ids"
    />
    <div class="form-actions text-right">
    <button class="btn" type="submit">
    Submit
    </button>
    </div>
    </Form>
);

}

GatewayUnprocessedRefundsConfig.title = 'Gateway Unprocessed Refunds Config';
