import axios from 'axios';


export const getSettings = () => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/settings`,
    method: 'GET',
    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
});

export const doPluginTest = (data) => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/pluginTest`,
    method: 'POST',
    data,
    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
});

export const savePostTypeSettings = (data) => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/settings/postTypes`,
    method: 'POST',
    data,
    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
});

export const saveUserPermission = (data) => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/settings/userPermissions`,
    method: 'POST',
    data,
    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
});

export const saveDeveloperSettings = (data) => axios({
    url:`${wpApiSettings.root}crop_thumbnails/v1/settings/developerSettings`,
    method: 'POST',
    data,
    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
});
