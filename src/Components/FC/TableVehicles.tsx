import Table from '@hubleto/react-ui/components/fc/Table'
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces'
import FormVehicle from './FormVehicle'

const componentName = 'TableVehicles'
const parentApp = 'Hubleto/App/External/WaiBlue/VehicleLog'

const TableVehicles = (props: TableProps) => <Table
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/Vehicle'}
  baseUrlSlug='vehicle-log/vehicles'
  renderForm={(table: TableMeta) => <FormVehicle {...table.getDefaultFormProps()} />}
  {...props}
/>;

export default TableVehicles;
