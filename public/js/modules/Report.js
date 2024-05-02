class Report{
    constructor(data){
        this.content = this.makeContent(data);
    }

    makeContent(data){
        const content = data.reduce((acc, item) => {
            return acc + `${item}\n`;
        }, '');

        return content;
    }

    download(filename){
        const a = document.createElement('a');
        const blob = new Blob([this.content], {type: 'text/plain'});
        const url = window.URL.createObjectURL(blob);
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
    
}   

export { Report };
