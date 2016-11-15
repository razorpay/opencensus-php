import { Factory } from 'dfaqapi'
import { faker } from 'dfaqapi'

export default class CustomersFactory extends Factory {
  id = faker.internet.password(15, false, undefined, 'cus_')
  name = faker.name.findName()
  email = faker.internet.email()
  contact = faker.phone.phoneNumber('(+91) ##### #####')
  address = faker.address.streetAddress(true)
}
