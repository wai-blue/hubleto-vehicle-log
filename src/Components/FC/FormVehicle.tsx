import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import Form from '@hubleto/react-ui/components/fc/Form';
import { type FormMeta, type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';
import Table from '@hubleto/react-ui/components/fc/Table';

const componentName = 'FormVehicle';
const parentApp = 'Hubleto/App/External/WaiBlue/VehicleLog';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

// The inputs of the vehicle model, in the order of its describeForm().
const TabVehicle = (props: {form: FormMeta}) => <>
  {Object.keys(props.form.description?.inputs ?? {}).map((field: string) => <Input key={field} field={field} />)}
</>;

const TabBookings = (props: {form: FormMeta}) => {
  if (props.form.id <= 0) return <div className='alert alert-info'>{T.translate('Save the vehicle first.')}</div>;
  // Hubleto's table of bookings, limited to this vehicle; a new booking gets the vehicle.
  return <Table
    uid={props.form.uid + '_table_bookings'}
    parentForm={props.form}
    model={parentApp + '/Models/Booking'}
    baseUrlSlug='vehicle-log/bookings'
    endpointParams={{idVehicle: props.form.id}}
    formDefaultValues={{id_vehicle: props.form.id}}
  />;
};

const FormVehicle = (props: FormProps) => <Form
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/Vehicle'}
  urlSlug='vehicle-log/vehicles'
  tabs={{
    default: {title: T.translate('Vehicle'), content: (form: FormMeta) => <TabVehicle form={form} />},
    bookings: {title: T.translate('Bookings'), content: (form: FormMeta) => <TabBookings form={form} />},
  }}
  {...props}
/>

export default FormVehicle;
