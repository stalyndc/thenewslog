-- Seed initial tags for autocomplete functionality
INSERT INTO tags (name, slug) VALUES
('AI', 'ai'),
('Startups', 'startups'),
('Technology', 'technology'),
('Cybersecurity', 'cybersecurity'),
('Climate', 'climate'),
('Science', 'science'),
('Health', 'health'),
('Business', 'business'),
('Programming', 'programming'),
('Design', 'design')
ON DUPLICATE KEY UPDATE name=name;
