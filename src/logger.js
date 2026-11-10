import { getLoggerBuilder } from '@nextcloud/logger'

export default getLoggerBuilder()
	.setApp('grocerylist')
	.detectUser()
	.build()
