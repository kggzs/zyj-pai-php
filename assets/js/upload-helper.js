/**
 * 上传助手模块
 * 提供图片和视频上传功能，供其他页面调用
 */

class UploadHelper {
    constructor() {
        this.isUploading = false;
    }

    /**
     * 上传图片
     * @param {Blob} imageBlob - 图片Blob对象
     * @param {string} inviteCode - 邀请码
     * @param {Object} options - 配置选项
     * @param {Function} options.onStart - 开始上传回调
     * @param {Function} onSuccess - 上传成功回调
     * @param {Function} onError - 上传失败回调
     * @returns {Promise}
     */
    async uploadImage(imageBlob, inviteCode, options = {}) {
        if (this.isUploading) {
            console.warn('正在上传中，请勿重复操作');
            return;
        }

        if (!imageBlob || !inviteCode) {
            const error = new Error('请先拍摄照片或提供邀请码');
            if (options.onError) {
                options.onError(error);
            }
            throw error;
        }

        this.isUploading = true;

        if (options.onStart) {
            options.onStart();
        }

        try {
            const formData = new FormData();
            formData.append('image', imageBlob, 'photo.jpg');
            formData.append('invite_code', inviteCode);

            const response = await fetch('api/upload.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const text = await response.text();
                console.error('上传失败，HTTP状态:', response.status);
                console.error('响应内容:', text);
                const error = new Error('上传失败：服务器错误 ' + response.status);
                if (options.onError) {
                    options.onError(error);
                }
                throw error;
            }

            const text = await response.text();
            console.log('服务器响应:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON解析失败:', e);
                console.error('响应内容:', text);
                const error = new Error('服务器返回格式错误');
                if (options.onError) {
                    options.onError(error);
                }
                throw error;
            }

            if (data.success) {
                if (options.onSuccess) {
                    options.onSuccess(data);
                }
                return data;
            } else {
                const error = new Error(data.message || '上传失败');
                if (options.onError) {
                    options.onError(error);
                }
                throw error;
            }
        } catch (err) {
            console.error('上传错误:', err);
            if (options.onError) {
                options.onError(err);
            }
            throw err;
        } finally {
            this.isUploading = false;
        }
    }

    /**
     * 上传视频
     * @param {Blob} videoBlob - 视频Blob对象
     * @param {string} inviteCode - 邀请码
     * @param {Object} options - 配置选项
     * @param {Function} options.onStart - 开始上传回调
     * @param {Function} onSuccess - 上传成功回调
     * @param {Function} onError - 上传失败回调
     * @param {number} options.maxSize - 最大文件大小（字节），默认20MB
     * @returns {Promise}
     */
    async uploadVideo(videoBlob, inviteCode, options = {}) {
        if (this.isUploading) {
            console.warn('正在上传中，请勿重复操作');
            return;
        }

        if (!videoBlob || !inviteCode) {
            const error = new Error('请先完成录像或提供邀请码');
            if (options.onError) {
                options.onError(error);
            }
            throw error;
        }

        // 检查文件大小
        const maxSize = options.maxSize || (20 * 1024 * 1024); // 默认20MB
        if (videoBlob.size > maxSize) {
            const error = new Error(`录像文件过大，最大支持 ${Math.round(maxSize / 1024 / 1024)}MB`);
            if (options.onError) {
                options.onError(error);
            }
            throw error;
        }

        this.isUploading = true;

        if (options.onStart) {
            options.onStart();
        }

        try {
            const formData = new FormData();
            formData.append('video', videoBlob, 'record.webm');
            formData.append('invite_code', inviteCode);

            const response = await fetch('api/upload_video.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const text = await response.text();
                console.error('上传失败，HTTP状态:', response.status);
                console.error('响应内容:', text);
                const error = new Error('上传失败：服务器错误 ' + response.status);
                if (options.onError) {
                    options.onError(error);
                }
                throw error;
            }

            const text = await response.text();
            console.log('服务器响应:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON解析失败:', e);
                console.error('响应内容:', text);
                const error = new Error('服务器返回格式错误');
                if (options.onError) {
                    options.onError(error);
                }
                throw error;
            }

            if (data.success) {
                if (options.onSuccess) {
                    options.onSuccess(data);
                }
                return data;
            } else {
                const error = new Error(data.message || '上传失败');
                if (options.onError) {
                    options.onError(error);
                }
                throw error;
            }
        } catch (err) {
            console.error('上传错误:', err);
            if (options.onError) {
                options.onError(err);
            }
            throw err;
        } finally {
            this.isUploading = false;
        }
    }

    /**
     * 图片压缩
     * @param {Blob} blob - 原始图片Blob
     * @param {Object} options - 配置选项
     * @param {number} options.maxSize - 最大文件大小（字节），默认1MB
     * @param {number} options.maxWidth - 最大宽度，默认1280
     * @param {number} options.maxHeight - 最大高度，默认720
     * @param {number} options.quality - 初始质量，默认0.7
     * @returns {Promise<Blob>}
     */
    async compressImage(blob, options = {}) {
        return new Promise((resolve) => {
            const img = new Image();
            const url = URL.createObjectURL(blob);

            const maxSize = options.maxSize || (1 * 1024 * 1024); // 默认1MB
            const maxWidth = options.maxWidth || 1280;
            const maxHeight = options.maxHeight || 720;
            let quality = options.quality || 0.7;

            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // 限制最大尺寸
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');

                // 启用高质量缩放
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);

                // 释放URL对象
                URL.revokeObjectURL(url);

                // 转换为Blob
                canvas.toBlob((compressedBlob) => {
                    if (!compressedBlob) {
                        resolve(blob);
                        return;
                    }

                    // 如果仍然太大，逐步降低质量
                    if (compressedBlob.size > maxSize && quality > 0.3) {
                        quality -= 0.1;
                        canvas.toBlob((newBlob) => {
                            resolve(newBlob || compressedBlob);
                        }, 'image/jpeg', quality);
                    } else {
                        resolve(compressedBlob);
                    }
                }, 'image/jpeg', quality);
            };

            img.onerror = () => {
                URL.revokeObjectURL(url);
                resolve(blob);
            };

            img.src = url;
        });
    }

    /**
     * 检查是否正在上传
     * @returns {boolean}
     */
    isUploadingActive() {
        return this.isUploading;
    }
}

// 导出为全局变量
window.UploadHelper = UploadHelper;
