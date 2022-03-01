FROM python:3.9-slim

WORKDIR /usr/src/app
COPY requirements.txt app.py ./
COPY static/ ./static/
COPY flag /flag

RUN pip install --no-cache-dir -i https://pypi.tuna.tsinghua.edu.cn/simple -r requirements.txt\
    && useradd -m myapp \
    && mkdir "template" \
    && chown -R myapp:myapp ./template

USER myapp
CMD [ "python", "./app.py" ]