FROM node

# prepare a user which runs everything locally! - required in child images!
RUN useradd --user-group --create-home --shell /bin/false app

WORKDIR /app

RUN npm install -g @angular/cli && npm cache clean

CMD ng build -w
