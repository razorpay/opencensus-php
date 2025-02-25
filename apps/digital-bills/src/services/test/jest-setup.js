import '@testing-library/jest-dom';
import { TextEncoder, TextDecoder } from 'util';

Object.assign(global, { TextDecoder, TextEncoder, __STAGE__: 'production' });


process.env.UNIVERSE_PUBLIC_BILLME_BILL_BASE_URL = 'https://yourbill.me';
process.env.UNIVERSE_PUBLIC_BILLME_IFRAME_URL = 'https://billme.stage.razorpay.in';
