import GenericEntity from './GenericEntity';

export default class Reminders extends GenericEntity {
  resourceFields = ['reminder_enable'];

  resourceUrl = 'reminders/service/merchant_settings';
}
