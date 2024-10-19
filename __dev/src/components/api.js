import axios from 'axios';


export const getCropData= (params) => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/crop`,
    method: 'GET',
    headers: { 'X-WP-Nonce': wpApiSettings.nonce },
    params
});

export const saveCrop= (data) => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/crop`,
    method: 'POST',
    headers: { 'X-WP-Nonce': wpApiSettings.nonce },
    data
});