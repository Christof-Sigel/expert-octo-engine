import os
from pathlib import Path

for root, dirs, files in os.walk(".", topdown=False):
	for name in files:
		if "\\" in name:
			thing = Path(name.replace("\\","/"))
			thing.parent.mkdir(parents = True, exist_ok = True)
			os.rename(name, name.replace("\\","/"))
