import { Factory } from 'dfaqapi';
import { faker } from 'dfaqapi';

export default class CustomersFactory extends Factory {
  id = faker.internet.password(15, false, undefined, 'cus_');
  customer_name = faker.name.findName();
  customer_email = faker.internet.email();
  customer_contact = faker.phone.phoneNumber('(+91) ##### #####');
  customer_address = faker.address.streetAddress(true);
}
