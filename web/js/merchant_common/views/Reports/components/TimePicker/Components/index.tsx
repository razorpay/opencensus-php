import { lazy } from 'react';

export const DTRPTimePicker = lazy(
  () => import(/* webpackChunkName: "DTRPTimePicker" */ './DTRPTimePicker'),
);

export const TimePickerFieldPicker = lazy(
  () => import(/* webpackChunkName: "TimePickerFieldPicker" */ './BaseTimePicker'),
);
