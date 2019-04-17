import React from 'react';
import { ModalContent } from 'component/Modal';

import { notifyError, notifySuccess, closeModal, openModal } from 'common/modal';
import { adminFormUpload } from 'common/fetch';

import Form from 'ui/Form';
import { FileField, SelectMode, SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

export default ({ merchantId }) => {
    function handleSubmit(body) {
        let mode = body.mode;
        delete body.mode;

        if (!body.file) {
            notifyError('Please upload batch file');

            return false;
        }

        body.file = body.file[0];

        return adminFormUpload(
            {
                type: 'sub_merchant',
                partner_id: merchantId,
                ...body,
            },
            `/admin/api/${mode}/admin/batches`
        ).then(response => {
            if (response.data.success) {
                notifySuccess('Uploaded successfully.');
                // closeModal();
                openModal(
                    <ModalContent header="Api Response:" noPadding>
                        <div class="code" style={{ width: '650px' }}>
                            {JSON.stringify(response.data.data, null, 4)}}
                        </div>
                    </ModalContent>
                );
            } else {
                response.data.errors.map(error => notifyError(error));
            }
        })
        .catch(err => {
            notifyError(JSON.stringify(err.response));
        });
    }

    return (
        <ModalContent header="Submerchant Batch Upload">
            <Form class="full-span full-elements" style={{ width: '400px' }}>
                <FileField
                    name="file"
                    label="Batch File"
                    accept="text/plain"
                />

                <SelectMode defaultValue="live" />

                <SelectField
                    name="use_email_as_dummy"
                    label="Use Email As Dummy"
                    defaultValue="1"
                >
                    <option value="1">True</option>
                    <option value="0">False</option>
                </SelectField>

                <SelectField
                    name="autofill_details"
                    label="Auto Fill Form"
                    defaultValue="0"
                >
                    <option value="1">True</option>
                    <option value="0">False</option>
                </SelectField>

                <SelectField
                    name="auto_submit"
                    label="Auto Submit Form"
                    defaultValue="0"
                >
                    <option value="1">True</option>
                    <option value="0">False</option>
                </SelectField>

                <SelectField
                    name="auto_activate"
                    label="Auto Activate Submerchants"
                    defaultValue="0"
                >
                    <option value="1">True</option>
                    <option value="0">False</option>
                </SelectField>

                <AsyncButton
                    text="Submit"
                    class="btn"
                    pendingClass="small spinner"
                    onSubmit={handleSubmit}
                />
            </Form>
        </ModalContent>
    );
};
