from math import isclose
import numpy as np
from typing import List


class Triangulate:
    def __init__(self, polygon: List[List[float]]) -> None:
        if self.is_clockwise(polygon):
            self.polygon = polygon
        else:
            self.polygon = polygon[::-1]
        return

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
    
    def is_clockwise(self, polygon):
        # 多角形が時計回りかどうかを判定
        return self.signed_area(polygon) < 0
    
    @staticmethod
    def is_convex(prev, curr, next):
        """与えられた3つの頂点が凸であるかを判定"""
        dx1 = curr[0] - prev[0]
        dy1 = curr[1] - prev[1]
        dx2 = next[0] - curr[0]
        dy2 = next[1] - curr[1]
        cross_product = dx1 * dy2 - dy1 * dx2
        return cross_product < 0  # 反時計回りが凸
    
    def is_ear(self, plgn, i):
        """polygonの頂点iが耳であるかを判定"""
        prev = plgn[i - 1]
        curr = plgn[i]
        next = plgn[(i + 1) % len(plgn)]
    
        if not self.is_convex(prev, curr, next):
            return False
    
        # 他の点が三角形(prev, curr, next)内にないことを確認
        for j, p in enumerate(self.polygon):
            if j != i and j != (i - 1) and j != (i + 1) % len(plgn):
                if self.point_in_triangle(p, prev, curr, next):
                    return False
        return True
    
    @staticmethod
    def point_in_triangle(pt, v1, v2, v3):
        """点ptが三角形(v1, v2, v3)内にあるかを確認"""
        def sign(p1, p2, p3):
            return (p1[0] - p3[0]) * (p2[1] - p3[1]) - (p2[0] - p3[0]) * (p1[1] - p3[1])
    
        b1 = sign(pt, v1, v2) < 0.0
        b2 = sign(pt, v2, v3) < 0.0
        b3 = sign(pt, v3, v1) < 0.0
    
        return (b1 == b2) and (b2 == b3)
    
    def triangulate(self):
        """凹多角形を三角形に分割し、三角形の頂点インデックスを返す"""
        if len(self.polygon) < 3:
            return []
        
        triangles = []
        indices = list(range(len(self.polygon)))  # インデックスのリスト
        while len(indices) > 3:
            ear_found = False
            for i in range(len(indices)):
                if self.is_ear([self.polygon[idx] for idx in indices], i):
                    # 耳が見つかったらインデックスを使って三角形を作る
                    prev = indices[i - 1]
                    curr = indices[i]
                    next = indices[(i + 1) % len(indices)]
                    triangles.append([prev, curr, next])
                    # 耳を削除
                    del indices[i]
                    ear_found = True
                    break
            if not ear_found:
                raise Exception("Triangulation failed. Invalid polygon.")
        # 残った最後の三角形を追加
        triangles.append(indices)
        return triangles

# 凹多角形の頂点 (反時計回り)
# polygon = [(0, 0), (2, 1), (1, 2), (3, 3), (0, 4)]

# 三角形分割（インデックスリストを返す）
# triangles = triangulate(polygon)

# 結果を表示
#for tri in triangles:
#    print(tri)
