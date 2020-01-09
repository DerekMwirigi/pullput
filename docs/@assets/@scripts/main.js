docs = {
    getApis: function() {
        xit.files.readJsonFile('../config/api.config.json').then(function(content) {
            for (apiModel in content.endpoints) {
                docs.displayApi(apiModel, content.endpoints[apiModel])
            }
        }).catch(function(error) {
            alert(error)
        })
    },
    displayApi: function(name, apiModel) {
        var htmlCode = '<h5>' + name + '</h5>'
        for (configModel in apiModel) {
            htmlCode += '<div class="api">' + docs.displayApiProperties(configModel, apiModel[configModel]) + '</div>'
        }
        $('#apis').append('<div class="group">' + htmlCode + '</div>')
    },
    displayApiProperties: function(name, properties) {
        var htmlCode = '<h5>' + name + '</h5>'
        for (property in properties) {
            htmlCode += '<div class="property" id="' + name + '" onclick="docs.view(this)"> <span>' + property + '</span> ' + properties[property] + ' </div>'
        }
        return htmlCode
    },
    view: function(view) {
        alert($(view).attr("id"))
    }
}

$(document).ready(function() {
    let config;
    xit.files.readJsonFile('../config/app.config.json').then(function(content) {
        config = (content);
        $(document).prop('title', config.name + ' (' + config.version + ')');
        $('#hHead').html(config.name + '<span> Version ' + config.version + '</span>');
        docs.getApis()
    }).catch(function(error) {
        alert(error);
    })

})