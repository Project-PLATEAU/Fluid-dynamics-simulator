import math

FACTOR = 273.15
PRESSURE = 760.0  # 大気圧 (mmHg)

def convert_to_kelvin(celsius:float) -> float:
    return celsius + FACTOR

def convert_to_celsius(kelvin:float) -> float:
    return kelvin - FACTOR

def calc_saturation_vapor_pressure(t_celsius):
    temp_kelvin = t_celsius + FACTOR
    z1 = 373.16 / temp_kelvin
    z2 = z1 - 1
    z3 = math.log(z1) / math.log(10)
    z4 = 11.344 * (1 - temp_kelvin / 373.16)
    z5 = math.log(1013.25) / math.log(10)
    z6 = -3.49149 * z2
    ew = -7.90298 * z2 + 5.02808 * z3 - 1.3816E-07 * (10 ** z4 - 1) + 0.0081328 * (10 ** z6 - 1) + z5
    sv = (10 ** ew) * 0.750062
    return sv

 # 相対湿度(%) → 絶対湿度(kg/kg)
def convert_to_absolute_humidity(relative_humidity:float, temperature_celsius:float) -> float:
    rh = relative_humidity              # 相対湿度 %
    t_celsius = temperature_celsius     # 温度 ℃

    def calc_actual_vapor_pressure(rh, sv): # 水蒸気圧を計算
        av = rh * sv / 100
        return av

    def calc_absolute_humidity(vp, p): # 絶対湿度を計算 (kg/kg)
        ah = 622.0 * vp / (p - vp) / 1000
        return ah

    sv_pressure = calc_saturation_vapor_pressure(t_celsius)     # 飽和水蒸気圧を計算
    av_pressure = calc_actual_vapor_pressure(rh, sv_pressure)   # 水蒸気圧を計算
    ah = calc_absolute_humidity(av_pressure, PRESSURE)          # 絶対湿度を計算 (kg/kg)

    return ah

# 絶対湿度(kg/kg) → 相対湿度(%)
def convert_to_relative_humidity(absolute_humidity:float, temperature_kelvins:float) -> float:
    ah = absolute_humidity              # 絶対湿度 kg/kg
    t_celsius = convert_to_celsius(temperature_kelvins)     # 温度 ℃

    def calc_actual_vapor_pressure(ah, p):
        vp = ah * p / (0.622 + ah)
        return vp

    def calc_relative_humidity(vp, sv):
        rh = (vp / sv) * 100
        return rh

    sv_pressure = calc_saturation_vapor_pressure(t_celsius)  # 飽和水蒸気圧を計算
    av_pressure = calc_actual_vapor_pressure(ah, PRESSURE)   # 水蒸気圧を計算
    rh = calc_relative_humidity(av_pressure, sv_pressure)    # 相対湿度を計算 (%)

    return rh

if __name__ == "__main__":
    f = convert_to_absolute_humidity(50, 26.85)
    g = 302.716 - 273.15
    h = convert_to_relative_humidity(0.0200624, g)
    print (f)
    print (h)