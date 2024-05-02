import { Report } from './modules/Report.js';

const downloadButton = document.getElementById('downloadButton');
// get all divs with class 'tax'
const taxTable = document.querySelectorAll('.tax');

const taxData = Array.from(taxTable).slice(1).reduce((acc, item) => {
    acc.push(item.textContent);
    return acc;
}, []);

downloadButton.addEventListener('click', function() {
    console.log('Output data: ', taxData);
    new Report(taxData).download('taxes.txt');
});
