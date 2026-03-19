
class Clockwise:
    def __init__(self, polygon):
        self.polygon = polygon
    @staticmethod
    def signed_area(polygon):
        # 多角形の符号付き面積を計算
        n = len(polygon)
        area = 0.0
        for i in range(n):
            x1, y1 = polygon[i]
            x2, y2 = polygon[(i + 1) % n]
            area += (x1 * y2 - x2 * y1)
        return area / 2.0
    
    def is_clockwise(self):
        # 多角形が時計回りかどうかを判定
        return self.signed_area(self.polygon) < 0
