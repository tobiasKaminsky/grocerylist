import { createAppConfig } from '@nextcloud/vite-config';

export default createAppConfig({
    main: 'src/main.js',
}, {
    /* Settings this to true would inline all styles
     * from Vue components and imported css into the JS bundle.
     * But as it increases the bundle size the JS parsing time will be longer -> bad for perfomance!
     */
    inlineCSS: false,
});
