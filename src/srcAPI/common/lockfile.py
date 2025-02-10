import sys
import os
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from common.utils import get_shared_folder, get_folder_path
from common import log_writer
logger = log_writer.getLogger()
log_writer.fileConfig()

class LockMag:
    def __init__(self, stl_files: list) -> None:
        """
        Initialize the LockMag class with a list of STL files.

        Args:
            stl_files (list)
        """
        self.full_path_list = self._get_full_paths(stl_files)

    def _get_full_paths(self, stl_files: list) -> list:
        """
        Convert the provided relative paths to full paths.

        Args:
            stl_files (list)
        """
        full_paths = []
        for stl_file in stl_files:
            full_path = os.path.join(get_shared_folder(), stl_file)
            logger.info(f'Converted full path: {full_path}')
            full_paths.append(full_path)
        return full_paths

    def is_exist_lockfile(self) -> bool:
        """
        STLファイルに対応する各ディレクトリ内に存在するlockfileを削除

        Return:
            bool: lockfileが存在すればTrue、無ければFalse
        """
        for file_path in self.full_path_list:
            folder_path, _ = get_folder_path(file_path)
            lockfile_path = os.path.join(folder_path, 'lockfile')
            if os.path.exists(lockfile_path):
                logger.info(f'File path where lockfile exists: {lockfile_path}')
                return True
        return False

    def create_lockfile(self) -> bool:
        """
        STLファイルに対応する各ディレクトリ内にlockfileを作成
        """
        for file_path in self.full_path_list:
            folder_path, _ = get_folder_path(file_path)
            lockfile_path = os.path.join(folder_path, 'lockfile')
            try:
                with open(lockfile_path, 'w') as lockfile:
                    pass
                logger.info(f"Lockfile created at: {lockfile_path}")
            except Exception as e:
                logger.error(f"Failed to create lockfile at {lockfile_path}: {e}")
                return False
        return True

    def delete_lockfile(self) -> bool:
        """
        STLファイルに対応する各ディレクトリ内に存在するlockfileを削除
        """
        for file_path in self.full_path_list:
            folder_path, _ = get_folder_path(file_path)
            lockfile_path = os.path.join(folder_path, 'lockfile')
            try:
                if os.path.exists(lockfile_path):
                    os.remove(lockfile_path)
                    logger.info(f"Lockfile deleted at: {lockfile_path}")
                else:
                    logger.info(f"No lockfile to delete at: {lockfile_path}")
            except Exception as e:
                logger.error(f"Failed to delete lockfile at {lockfile_path}: {e}")
                return False
        return True

#def main():
    # stl_files = ["sample/1/a.txt", "sample/2/b.txt"]
    # lock_mag = LockMag(stl_files)

    # try:
    #     if lock_mag.is_exist_lockfile():
    #         print("ロックファイルがいずれかのディレクトリに存在します。")
    #     else:
    #         print("いずれのディレクトリにもロックファイルは見つかりませんでした。")

    #     if lock_mag.create_lockfile():
    #         print("ロックファイルを作成しました。")
    #     else:
    #         print("ロックファイルの作成に失敗しました。")

    #     if lock_mag.delete_lockfile():
    #         print("ロックファイルを削除しました。")
    #     else:
    #         print("ロックファイルの削除に失敗しました。")
    # except Exception as e:
    #     print(f"エラー: {e}")

# if __name__ == "__main__":
#     main()