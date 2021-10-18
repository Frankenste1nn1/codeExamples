const async = require('async');
const axios = require('axios');
const common = require('_basePath_/common.js');
const routing = require('_basePath_/server/config/routing.js');
const login = require('_basePath_/system/Login');

function registerCustomerFacebook(req) {
    return new Promise((resolve, reject) => {
        const url = common.system.getCustomerBackendHost(routing.v1.customerRegFacebook.post.url);
        async.auto({
            "checkCustomer": function (callback) {
                const response = {
                    success: true
                };

                axios.get(
                    `https://graph.facebook.com/me?access_token=${req.body.accessToken}&fields=id,name,email,first_name,last_name`,
                )
                    .then(facebookResponse => {
                        response.data = facebookResponse.data;
                        callback(null, response);
                    })
                    .catch(error => {
                        response.success = false;
                        response.error = error.response.data.error
                        callback(response);
                    });
            },
            registerCustomer: ['checkCustomer', function (results, callback) {
                if (results.checkCustomer && results.checkCustomer.success) {
                    const data = results.checkCustomer.data;

                    data.customerUuid = common.system.uniqueIntId();
                    data.customerAddressUuid = common.system.uniqueIntId();
                    data.addedDate = common.system.setCurrentDateTime();
                    data.customerType = req.body.customerType;
                    data.timezoneId = req.body.timezoneId;
                    data.customerShippingFrequency = req.body.customerShippingFrequency;

                    common.system.postDataToBackend(data, url, req).then((response) => {
                        const responseData = response;

                        if (response != null && response.success) {
                            responseData.content.responseData = response.content;

                            responseData.content.responseData.accessToken = common.system.createToken(
                                response,
                                common.config.JWT_SECRET,
                                common.config.JWT_EXPIRATION
                            );
                            responseData.content.responseData.refreshToken = common.system.createToken(
                                response,
                                common.config.JWT_REFRESH_SECRET,
                                common.config.JWT_REFRESH_EXPIRATION
                            );

                            login.saveTokenInRedis(responseData, common.config.REDIS_CUSTOMER_DB_ID);

                            responseData.content.responseData.accessToken = common.system.createEncryptedToken(
                                response,
                                common.config.JWT_SECRET,
                                common.config.JWT_EXPIRATION
                            );
                            responseData.content.responseData.refreshToken = common.system.createEncryptedToken(
                                response,
                                common.config.JWT_REFRESH_SECRET,
                                common.config.JWT_REFRESH_EXPIRATION
                            );

                            delete responseData.content.responseData;
                        }

                        callback(null, responseData);
                    }).catch(callback);
                } else {
                    callback(results.checkCustomer);
                }
            }]
        }, function (err, results) {
            if (err) {
                reject(err);
            } else {
                resolve(results.registerCustomer);
            }
        });
    });
}

module.exports = {
  registerCustomerFacebook
};
