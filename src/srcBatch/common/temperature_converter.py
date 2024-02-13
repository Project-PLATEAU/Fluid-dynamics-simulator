
FACTOR = 273.15

def convert_to_kelvin(celsius:float) -> float:
    return celsius + FACTOR

def convert_to_celsius(kelvin:float) -> float:
    return kelvin - FACTOR

if __name__ == "__main__":
    f = convert_to_kelvin(26.85)
    print (f)