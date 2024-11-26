import moment from 'moment';
import * as yup from 'yup';

interface DownloadFormValues {
  fileName: string;
  fileType: string;
  startDate: Date;
  endDate: Date;
  recipientEmail?: string;
}

const downloadFormSchema = yup.object<DownloadFormValues>({
  fileName: yup
    .string()
    .required('File name is required')
    .min(3, 'File name must be at least 3 characters long'),
  fileType: yup
    .string()
    .required('File type is required')
    .oneOf(['csv', 'xlsx', 'txt'], 'File type must be CSV, Excel, or Text'),
  startDate: yup.date().required('Start date is required'),
  endDate: yup
    .date()
    .required('End date is required')
    .max(moment().endOf('day').toDate(), 'End date cannot be in the future')
    .min(yup.ref('startDate'), 'End date must be after start date'),
  recipientEmail: yup.string().email('Invalid email').optional(),
});

export { downloadFormSchema };
