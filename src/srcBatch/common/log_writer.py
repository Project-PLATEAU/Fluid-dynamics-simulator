# ログ出力の設定内容は 'logging.config' を参照
# ログ出力ファイルは 'log/wrapper.log'
import logging
import logging.config

def fileConfig():
    logging.config.fileConfig('logging.config')

def getLogger():
    logger = logging.getLogger('bridgePlateauCfd')
    return logger

def format_str(model_id : str ,message : str):
    return  '''[%s] %s'''%(model_id,message)

## ログを取得したいファイルに下記追加ください。
# import log_writer
# logger = log_writer.getLogger()
#
# def main():
#     log_writer.fileConfig()
#
# 必要箇所に記載
#     model_id = "***"
#     logger.info(log_writer.format_str(model_id,"test message"))