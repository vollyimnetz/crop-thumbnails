/**
 * Check if the image should be marked as low-resolution image.
 * @param {object} image
 * @param {object} compareSize an object with the properties "width" and "height"
 * @returns {boolean} true if it should be marked as low-res
 */

export const isLowRes = (image, compareSize) => {
    if(!image.active || compareSize===null) {
        return false;
    }
    if(image.width===0 && compareSize.height < image.height) {
        return true;
    }
    if(image.height===0 && compareSize.width < image.width) {
        return true;
    }
    if(image.height===9999) {
        if(compareSize.width < image.width) {
            return true;
        }
        return false;
    }
    if(image.width===9999) {
        if(compareSize.height < image.height) {
            return true;
        }
        return false;
    }
    if(compareSize.width < image.width || compareSize.height < image.height) {
        return true;
    }
    return false;
};



export const getCenterPreselect = ( width, height, targetRatio ) => {
    let x0 = 0;
    let y0 = 0;
    let x1 = width;
    let y1 = height;
    let sourceRatio = width/height;

    if(sourceRatio <= targetRatio) {
        y0 = (height / 2) - ((width / targetRatio) / 2);
        y1 = height-y0;
    } else {
        x0 = (width / 2) - ((height * targetRatio) / 2);
        x1 = width-x0;
    }
    return [x0,y0,x1,y1];
};