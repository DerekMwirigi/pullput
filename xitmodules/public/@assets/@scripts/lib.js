xit = {
    debug: false,
    ui: {
        processPlaceHolder: '<img src="@assets/@images/loader.svg" alt="PullPut" class="center">',
        openpage: function(resourceUrl) {
            window.location.href = resourceUrl
        },
        openview: function(method, headers, data, resourceUrl, viewId, storeRoute) {
            if (xit.storage.getValue('currentView') == resourceUrl) {
                return
            }
            $(viewId).html(xit.ui.processPlaceHolder);
            switch (method) {
                case 'GET':
                    $.get(resourceUrl, function(response) {
                        $(viewId).html(response)
                    })
                    break
                case 'POST':
                    xit.request.post(headers, JSON.stringify(data), resourceUrl).then(function(response) {
                        $(viewId).html(response)
                    }).catch(function(e) {
                        $(viewId).html(e)
                    })
                    break
            }
            if (storeRoute) { xit.storage.saveItem('currentView', resourceUrl) }
        },
        openmodal: function(method, headers, data, resourceUrl, viewId, options, pd) {
            switch (method) {
                case 'GET':
                    $(viewId).modal('show').find('.modal-body').load(resourceUrl)
                    break
                case 'POST':
                    xit.request.post(headers, JSON.stringify(data), resourceUrl).then(function(response) {
                        $(viewId).modal('show').find('.modal-body').html(response)
                    }).catch(function(e) {
                        alert(e)
                    })
                    break
            }
        }
    },
    dates: {
        getDateTimeNow: function() {
            var today = new Date()
            var year = today.getFullYear()
            var month = parseInt((today.getMonth() + 1))
            if (month < 10) {
                month = '0' + month
            }
            var day = parseInt(today.getDate())
            if (day < 10) {
                day = '0' + day
            }
            var hour = parseInt(today.getHours())
            if (hour < 10) {
                hour = '0' + hour
            }
            var minute = parseInt(today.getMinutes())
            if (minute < 10) {
                minute = '0' + minute
            }
            var second = parseInt(today.getSeconds())
            if (second < 10) {
                second = '0' + second
            }
            var time = hour + ":" + minute + ":" + second;
            return year + '-' + month + '-' + day + ' ' + time
        },
        getDateNow: function() {
            var today = new Date()
            var year = today.getFullYear()
            var month = parseInt((today.getMonth() + 1))
            if (month < 10) {
                month = '0' + month
            }
            var day = parseInt(today.getDate())
            if (day < 10) {
                day = '0' + day
            }
            return year + '-' + month + '-' + day
        },
        changeFormat: function(dateString) {
            var today = new Date(dateString)
            var year = today.getFullYear()
            var month = parseInt((today.getMonth() + 1))
            if (month < 10) {
                month = '0' + month
            }
            var day = parseInt(today.getDate())
            if (day < 10) {
                day = '0' + day
            }
            return year + '-' + month + '-' + day
        },
        uniquecNo: function() {
            var dateTime = this.getDateTime()
            dateTime = dateTime.replace(' ', '')
            return dateTime.replace(/[`~!@#$%^&*()_|+\-=?;:'",.<>\{\}\[\]\\\/]/gi, '')
        },
        startOfWeek: function(dateString) {
            var date = new Date(dateString)
            var diff = date.getDate() - date.getDay() + (date.getDay() === 0 ? -6 : 1)
            return this.changeFormat(new Date(date.setDate(diff)));
        },
        getDate_Add: function(dateString, noDays) {
            var date1 = new Date(dateString)
            date1.setDate(date1.getDate() + noDays);
            return this.changeFormat(date1)
        },
        getMonthFirstLastDates: function(dateString) {
            var date1 = new Date(dateString);
            var firstDay = new Date(date1.getFullYear(), date1.getMonth(), 1)
            var lastDay = new Date(date1.getFullYear(), date1.getMonth() + 1, 0)
            return [this.changeFormat(firstDay), this.changeFormat(lastDay)]
        },
        dateDiff: function(date1, date2) {
            var dt1 = new Date(date1)
            var dt2 = new Date(date2)
            return Math.floor((Date.UTC(dt2.getFullYear(), dt2.getMonth(), dt2.getDate()) - Date.UTC(dt1.getFullYear(), dt1.getMonth(), dt1.getDate())) / (1000 * 60 * 60 * 24))
        }
    },
    request: {
        post: function(headers, data, endpoint) {
            if (xit.debug) {
                console.log({ headers: headers, data: data, endpoint: endpoint })
            }
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.onload = function() {
                    resolve(this.response)
                    if (xit.debug) {
                        console.log(this.response)
                    }
                }
                xhr.onprogress = function(event) {
                    var percent = (event.loaded / event.total) * 100
                    if (xit.debug) {
                        console.log(percent)
                    }
                }
                xhr.onerror = function(e) {
                    reject(e);
                    if (xit.debug) {
                        console.log(e)
                    }
                }
                xhr.open('POST', endpoint, true);
                if (headers != null) {
                    for (var i in headers) {
                        var header = headers[i].split(':')
                        xhr.setRequestHeader(header[0], header[1])
                    }
                }
                xhr.send(data)
                xit.storage.saveItem('currentEndPoint', endpoint)
            });
        },
        get: function(headers, data, endpoint) {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.onload = function() {
                    resolve(this.response)
                    if (xit.debug) {
                        console.log(this.response)
                    }
                }
                xhr.onprogress = function(event) {
                    var percent = (event.loaded / event.total) * 100
                    if (xit.debug) {
                        console.log(percent)
                    }
                }
                xhr.onerror = function(e) {
                    reject(e);
                    if (xit.debug) {
                        console.log(e)
                    }
                }
                if (data != null) { xhr.open('GET', endpoint + $.param(data), true) } else { xhr.open('GET', endpoint, true) }
                if (headers != null) {
                    for (var i in headers) {
                        var header = headers[i].split(':')
                        xhr.setRequestHeader(header[0], header[1])
                    }
                }
                xhr.send()
                xit.storage.saveItem('currentEndPoint', endpoint)
            });
        }
    },
    files: {
        readJsonFile: function(filePath) {
            return new Promise(function(resolve, reject) {
                $.get(filePath, function(data, status) {
                    if (status == 'success') {
                        resolve(data)
                    } else {
                        reject(status)
                    }
                });
            })

        }
    },
    storage: {
        create: function(key) {
            if (typeof(Storage) !== "undefined") {
                sessionStorage.setItem(key, null);
            }
        },
        saveItem: function(key, value) {
            if (typeof(Storage) !== "undefined") {
                if (sessionStorage.getItem(key) != null) {
                    sessionStorage.setItem(key, value);
                } else {
                    this.create(key);
                    sessionStorage.setItem(key, value);
                }
            }
        },
        getValue: function(key) {
            if (key != null) {
                if (typeof(Storage) !== "undefined") {
                    return sessionStorage.getItem(key);
                }
            } else {
                return sessionStorage;
            }
        },
        destroy: function(key) {
            if (key != null) {
                if (typeof(Storage) !== "undefined") {
                    sessionStorage.setItem(key, null);
                    return true;
                }
            } else {
                return true;
            }
        }
    }
}

$(document).ready(function() {

})