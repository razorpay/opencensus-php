import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as Messages from '../Constant';
import useActivation from '../../hooks/useActivation';
import ActivationFormModal from '../ActivationFormModals';
import { render, screen, waitForElementToBeRemoved, cleanup } from 'test-utils';

afterEach(() => {
  cleanup();
});

const App = ({ isOpen, modaltype }) => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <ActivationFormModal isOpen={isOpen} modalType={modaltype} closeModal={() => {}} />;
};

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => screen.queryByText('Loading...'));

test('should open dedupe modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="dedupe" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.DEDUPE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.DEDUPE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.DEDUPE.buttonText)).toBeInTheDocument();
});

test('should open Poi initiated modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="poi_initiated" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.POI_INITIATED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.POI_INITIATED.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.POI_INITIATED.buttonText)).toBeInTheDocument();
});

test('should open Payment Enable modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="payment_enable" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.buttonText)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.secondryButtonText)).toBeInTheDocument();
});

test('should open Payment Disable modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="payment_disable" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.buttonText)).toBeInTheDocument();
});

test('should open TNC generate modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="tnc" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.TNC.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.TNC.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.TNC.buttonText)).toBeInTheDocument();
});

test('should open Needs clarification modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="needs_clarification" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.NC.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.NC.description.normal_nc)).toBeInTheDocument();
  expect(screen.getByText(Messages.NC.buttonText)).toBeInTheDocument();
});

test('should open Rejected modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="rejected" />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.REJECTED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.REJECTED.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.REJECTED.buttonText)).toBeInTheDocument();
});
